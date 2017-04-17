<?php

namespace Gabrielqs\Cielo\Model;

use \Magento\Framework\Exception\LocalizedException;
use \Magento\Payment\Model\InfoInterface;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Api\ExtensionAttributesFactory;
use \Magento\Framework\Api\AttributeValueFactory;
use \Magento\Payment\Helper\Data;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Payment\Model\Method\Logger;
use \Magento\Framework\Module\ModuleListInterface;
use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use \Magento\Directory\Model\CountryFactory;
use \Magento\Quote\Api\Data\CartInterface;
use \Magento\Framework\Validator\Exception;
use \Magento\Payment\Model\Method\Cc;
use \Gabrielqs\Cielo\Helper\Webservice\Data as WebserviceHelper;
use \Gabrielqs\Cielo\Helper\Webservice\Installments as InstallmentsHelper;
use \Gabrielqs\Cielo\Model\Webservice\Api;
use \Magento\Sales\Model\Order\Payment\Transaction;
use \Magento\Framework\DataObject;
use \Magento\Payment\Model\Method\AbstractMethod as AbstractPaymentMethod;
use \Magento\Framework\Phrase;


/**
 * Cielo Webservice Credit Card Payment Method
 */
class Webservice extends Cc
{

    const CODE = 'cielo_webservice';

    /**
     * Webservice Info Block
     * @var string
     */
    protected $_infoBlockType = 'Gabrielqs\Cielo\Block\Webservice\Info';

    /**
     * Can Authorize
     * @var bool
     */
    protected $_canAuthorize                = true;

    /**
     * Can Capture
     * @var bool
     */
    protected $_canCapture                  = true;

    /**
     * Can Capture Partial
     * @var bool
     */
    protected $_canCapturePartial           = false;

    /**
     * Can Refund
     * @var bool
     */
    protected $_canRefund                   = true;

    /**
     * Can Refund Partial
     * @var bool
     */
    protected $_canRefundInvoicePartial     = false;

    /**
     * Cielo Payment Method Code
     * @var string
     */
    protected $_code                        = self::CODE;

    /**
     * Is Payment Gateway?
     * @var bool
     */
    protected $_isGateway                   = true;

    /**
     * Is Offline Payment=
     * @var bool
     */
    protected $_isOffline                   = false;

    /**
     * Supported Currency Codes
     * @var string[]
     */
    protected $_supportedCurrencyCodes      = ['BRL'];

    /**
     * Country Factory
     * @var CountryFactory|null
     */
    protected $_countryFactory              = null;

    /**
     * Cielo API Model
     * @var Api|null
     */
    protected $_api                         = null;

    /**
     * Has Api Been Initialized?
     * @var boolean
     */
    protected $_isApiInitialized            = false;

    /**
     * Cielo Helper
     * @var WebserviceHelper|null
     */
    protected $_webserviceHelper            = null;

    /**
     * Cielo Helper
     * @var WebserviceHelper|null
     */
    protected $_installmentsHelper          = null;

    /**
     * Cielo constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ModuleListInterface $moduleList
     * @param TimezoneInterface $localeDate
     * @param CountryFactory $countryFactory
     * @param Api $api
     * @param WebserviceHelper $webserviceHelper
     * @param InstallmentsHelper $installmentsHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ModuleListInterface $moduleList,
        TimezoneInterface $localeDate,
        CountryFactory $countryFactory,
        Api $api,
        WebserviceHelper $webserviceHelper,
        InstallmentsHelper $installmentsHelper,
        array $data = []
    ) {
        $this->_countryFactory = $countryFactory;
        $this->_api = $api;
        $this->_webserviceHelper = $webserviceHelper;
        $this->_installmentsHelper = $installmentsHelper;

        return parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
    }

    /**
     * Assign data to info model instance
     *
     * @param DataObject $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();
        $installmentQuantity = $data->getAdditionalData('installment_quantity') ?
            (int) $data->getAdditionalData('installment_quantity') : 1;
        $info->setAdditionalInformation('installment_quantity', ($installmentQuantity));
        $this->_installmentsHelper->setInstallmentDataBeforeAuthorization($installmentQuantity);

        return $this;
    }

    /**
     * Payment Authorization
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        try {
            /**
             * @var \SimpleXMLElement $responseAuth
             */
            $responseAuth = $this->_getApi()->makeAuthRequest($payment, $amount);

            if (is_object($responseAuth)) {
                $transactionStatus = true; #(((string) $responseAuth->status) == Api::TRANSACTION_STATUS_AUTHORIZED);
                $tid = time(); #(string) $responseAuth->tid;

                if ($transactionStatus) {
                    /*
                     * Creating a transaction, type = order.
                     * This transaction is needed in order for the capture transaction be made online, generating
                     * a capture transaction when the invoice is created
                     */
                    $payment->setTransactionId($tid . '-order');
                    $payment->addTransaction(Transaction::TYPE_ORDER);

                    /*
                     * After the complete processing of the order, when using the order_payment method, a new
                     * transaction of the Auth type will be created. This transaction is needed in order of us to be
                     * able to create online refunds
                     */
                    $payment
                        ->setAmount($amount)
                        ->setStatus(self::STATUS_APPROVED)
                        ->setIsTransactionPending(false)
                        ->setCcTransId($tid)
                        ->setTransactionId($tid)
                        ->setCcNumber(null)
                        ->setCcCid(null)
                        ->setCcNumberEnc(null)
                        ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS,
                            ['Cielo Auth Response' => utf8_encode((string) $responseAuth->asXML())]);

                    /*
                     * Recurring payments, disabled for now...
                     *

                     if (Mage::getStoreConfigFlag('payment/cielooneclick/active')) {
                        $payment->setAdditionalInformation('cielooneclick_token',
                            (string)$responseAuth->token->{'dados-token'}->{'codigo-token'});
                    } */

                    /*
                     * AVS Mode
                     */
                    if ($this->_webserviceHelper->isAvsActive()) {
                        if (isset($responseAuth->autorizacao->{'mensagem-avs-cep'})) {
                            $msg = (string) $responseAuth->autorizacao->{'mensagem-avs-cep'};
                            $payment->setAdditionalInformation('cielo_avs_cep', $msg);
                        }
                        if (isset($responseAuth->autorizacao->{'mensagem-avs-end'})) {
                            $msg = (string) $responseAuth->autorizacao->{'mensagem-avs-end'};
                            $payment->setAdditionalInformation('cielo_avs_endereco', $msg);
                        }
                    }
                } else {
                    $payment
                        ->setAmount(0)
                        ->setStatus(self::STATUS_DECLINED)
                        ->setIsTransactionPending(true)
                        ->setCcNumber(null)
                        ->setCcCid(null)
                        ->setCcNumberEnc(null)
                        ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS,
                            ['Cielo Auth Response' => utf8_encode((string) $responseAuth->asXML())]);

                    $msg = __('Unfortunately there was a problem with your payment, and your credit card was not' .
                        ' approved. Please check your payment information and try again.');
                    throw new LocalizedException(new Phrase($msg));
                }
            } else {
                $msg = __('We\'re sorry, but there was an unexpected error while processing your payment.' .
                    ' Please, try again.');
                throw new LocalizedException(new Phrase($msg));
            }
        } catch (Exception $e) {
            $this->debugData(['exception' => $e->getMessage()]);
            if (isset($requestData)) {
                $this->debugData(['request' => $requestData]);
            }
            $this->_logger->error(new Phrase('Payment authorization error'));
            throw new LocalizedException(new Phrase('Payment authorization error: ' . $e->getMessage()));
        }

        return $this;
    }

    /**
     * Availability for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, (array) $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * Captures previously authorized transaction
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if (!$payment->getCcTransId()) {
            throw new LocalizedException(new Phrase('No previous authorization transaction found, aborting capture'));
        }

        $responseCapt = $this->_getApi()->makeCaptureRequest($payment, $amount);

        $transactionStatus = true; #(((string)$responseCapt->status) == Api::TRANSACTION_STATUS_CAPTURED);
        $tid = $payment->getCcTransId(); #string)$responseCapt->tid;

        if ($transactionStatus) {
            print 1;
            $payment
                ->setAmount($amount)
                ->setStatus(self::STATUS_SUCCESS)
                ->setTransactionId($tid . '-capture')
                ->setParentTransactionId($tid)
                ->setIsTransactionClosed(0)
                ->setIsTransactionPending(false)
                ->setCcNumber(null)
                ->setCcCid(null)
                ->setCcNumberEnc(null)
                ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS,
                    ['Cielo Capture Response' => utf8_encode((string) $responseCapt->asXML())])
                ->save();
            print 2;
        } else {
            $payment
                ->setStatus(self::STATUS_ERROR)
                ->setCcNumber(null)
                ->setCcCid(null)
                ->setCcNumberEnc(null)
                ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS,
                    ['Cielo Capture Response' => utf8_encode((string) $responseCapt->asXML())]);;

            throw new LocalizedException(new Phrase('It was not possible to capture the transaction'));
        }
        return $this;
    }

    /**
     * Returns an initialized instance of the Webservice Api class
     * @return Api
     */
    protected function _getApi()
    {
        if (!$this->_isApiInitialized) {
            $this->_api->setAccessKey($this->_webserviceHelper->getAccessKey());
            $this->_api->setAvsActive($this->_webserviceHelper->isAvsActive());
            $this->_api->setCompanyId($this->_webserviceHelper->getCompanyId());
            $this->_api->setTestMode($this->_webserviceHelper->isTest());

            $this->_isApiInitialized = true;
        }
        return $this->_api;
    }

    /**
     * Adding Brazilian Credit Card Types
     * @return array
     */
    public function getVerificationRegEx()
    {
        $verificationExpList = parent::getVerificationRegEx();

        $verificationExpList['AU'] = '/^[0-9]{3}$/';
        $verificationExpList['EL'] = '/^[0-9]{3}$/';
        $verificationExpList['HI'] = '/^[0-9]{3}$/';

        return $verificationExpList;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->getConfigData('active')) {
            return false;
        }
        
        if ($quote && ($quote->getBaseGrandTotal() <= $this->getConfigData('min_order_total') ||
                (
                    $this->getConfigData('max_order_total') &&
                    $quote->getBaseGrandTotal() > $this->getConfigData('max_order_total')
                )
            )
        ) {
            return false;
        }

        if ((!$this->_webserviceHelper->getCompanyId() || !$this->_webserviceHelper->getAccessKey()) &&
            (!$this->_webserviceHelper->isTest())) {
            return false;
        }

        return parent::isAvailable($quote);
    }


    /**
     * Refunds transaction
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(new Phrase('Refund action is not available'));
        }

        $responseRefund = $this->_getApi()->makeRefundRequest($payment, $amount);

        $refundStatus = (((string) $responseRefund->status) == Api::TRANSACTION_STATUS_CANCELED);
        $tid = (string)$responseRefund->tid;

        if (!$refundStatus) {
            throw new LocalizedException(new Phrase(
                'There was a problem while refunding this order, please try again later'
            ));
        }

        $payment
            ->setTransactionId($tid . '-refund')
            ->setTransactionAdditionalInfo(Transaction::RAW_DETAILS,
                ['Cielo Refund Response' => utf8_encode((string) $responseRefund->asXML())]);
        $payment->addTransaction(Transaction::TYPE_REFUND);

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @codeCoverageIgnore
     */
    public function validate()
    {
        /* calling parent validate function */
        AbstractPaymentMethod::validate();

        $info = $this->getInfoInstance();
        $errorMsg = false;
        $availableTypes = explode(',', $this->getConfigData('cctypes'));

        $ccNumber = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        $ccType = '';
        if (in_array($info->getCcType(), $availableTypes)) {
            if ($this->validateCcNum(
                    $ccNumber
                ) || $this->otherCcType(
                    $info->getCcType()
                ) && $this->validateCcNumOther(
                // Other credit card type number validation
                    $ccNumber
                )
            ) {
                $ccTypeRegExpList = [
                    //Solo, Switch or Maestro. International safe
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)' .
                        '|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)' .
                        '|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))' .
                        '|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))' .
                        '|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',
                    // Visa
                    'VI' => '/^4[0-9]{12}([0-9]{3})?$/',
                    // Master Card
                    'MC' => '/^5[1-5][0-9]{14}$/',
                    // American Express
                    'AE' => '/^3[47][0-9]{13}$/',
                    // Discover
                    'DI' => '/^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})' .
                        '|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}' .
                        '|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}' .
                        '|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}' .
                        '|5[0-9]{14}))$/',
                    // JCB
                    'JCB' => '/^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}' .
                        '|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}' .
                        '|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}' .
                        '|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}' .
                        '|5[0-9]{14}))$/',

                    /* Changes by Gabrielqs - Brazilian Credit Card Types - Beggining */
                    'EL' => '/^(40117(8|9)|431274|438935|636297|451416|45763(1|2)|504175|5067((17)|(18)|(22)|(25)|' .
                        '(26)|(27)|(28)|(29)|(30)|(33)|(39)|(40)|(41)|(42)|(44)|(45)|(46)|(47)|(48))|627780|636297|' .
                        '636368)[0-9]{10}$/',
                    'AU' => '/^50[0-9]{17}$/',
                    'HI' => '/^(384100|384140|384160|606282)([0-9]{10}|[0-9]{13})$/'
                    /* Changes by Gabrielqs - Brazilian Credit Card Types - End  */
                ];

                $ccNumAndTypeMatches = isset(
                        $ccTypeRegExpList[$info->getCcType()]
                    ) && preg_match(
                        $ccTypeRegExpList[$info->getCcType()],
                        $ccNumber
                    );
                $ccType = $ccNumAndTypeMatches ? $info->getCcType() : 'OT';

                if (!$ccNumAndTypeMatches && !$this->otherCcType($info->getCcType())) {
                    $errorMsg = __('The credit card number doesn\'t match the credit card type.');
                }
            } else {
                $errorMsg = __('Invalid Credit Card Number');
            }
        } else {
            $errorMsg = __('This credit card type is not allowed for this payment method.');
        }

        //validate credit card verification number
        if ($errorMsg === false && $this->hasVerification()) {
            $verifcationRegEx = $this->getVerificationRegEx();
            $regExp = isset($verifcationRegEx[$info->getCcType()]) ? $verifcationRegEx[$info->getCcType()] : '';
            if (!$info->getCcCid() || !$regExp || !preg_match($regExp, $info->getCcCid())) {
                $errorMsg = __('Please enter a valid credit card verification number.');
            }
        }

        if ($ccType != 'SS' && !$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorMsg = __('Please enter a valid credit card expiration date.');
        }

        if ($errorMsg) {
            throw new LocalizedException(new Phrase($errorMsg));
        }

        return $this;
    }
}
