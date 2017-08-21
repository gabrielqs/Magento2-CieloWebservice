<?php

namespace Gabrielqs\Cielo\Model\Webservice;

use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Framework\View\Asset\Source;
use Magento\Checkout\Model\Session as CheckoutSession;
use Gabrielqs\Cielo\Helper\Webservice\Data as WebserviceHelper;
use Gabrielqs\Cielo\Helper\Webservice\Installments as InstallmentsHelper;

class ConfigProvider extends CcGenericConfigProvider
{

    /**
     * Webservice Helper
     * @var WebserviceHelper
     */
    protected $_webserviceHelper = null;

    /**
     * Checkout Session
     * @var CheckoutSession
     */
    protected $_checkoutSession = null;

    /**
     * Installments Helper
     * @var InstallmentsHelper
     */
    protected $_installmentsHelper = null;

    /**
     * Asset Source
     * @var Source
     */
    protected $_assetSource;

    /**
     * ConfigProvider constructor.
     *
     * @param CcConfig $ccConfig
     * @param PaymentHelper $paymentHelper
     * @param WebserviceHelper $webserviceHelper
     * @param Source $assetSource
     * @param InstallmentsHelper $installmentsHelper
     * @param CheckoutSession $checkoutSession
     * @param array $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        WebserviceHelper $webserviceHelper,
        Source $assetSource,
        InstallmentsHelper $installmentsHelper,
        CheckoutSession $checkoutSession,
        $methodCodes = []
    ) {
        $this->_assetSource = $assetSource;
        $this->_installmentsHelper = $installmentsHelper;
        $this->_webserviceHelper = $webserviceHelper;
        $this->_checkoutSession = $checkoutSession;
        $methodCodes[$this->_webserviceHelper->getMethodCode()] = $this->_webserviceHelper->getMethodCode();
        return parent::__construct($ccConfig, $paymentHelper, $methodCodes);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $method = $this->_webserviceHelper->getMethodCode();

        if (!$this->_webserviceHelper->getConfigData('active')) {
            return [
                'payment' => [
                    $method => [
                        'active' => false,
                    ]
                ]
            ];
        }

        return [
            'payment' => [
                $method => [
                    'active'        => true,
                    'icons'         => $this->_getIcons(),
                    'installments'  => $this->_installmentsHelper->getInstallments($this->_getPaymentAmount()),
                    'interestRates' => $this->_installmentsHelper->getInstallmentConfig()
                ]
            ]
        ];
    }

    /**
     * Get icons for available payment methods
     * @return array
     */
    protected function _getIcons()
    {
        $icons = [];
        $types = $this->ccConfig->getCcAvailableTypes();
        foreach (array_keys($types) as $code) {
            if (!array_key_exists($code, $icons)) {
                $asset = $this->ccConfig->createAsset('Gabrielqs_Cielo::images/cc/' . strtolower($code) . '.png');
                $placeholder = $this->_assetSource->findRelativeSourceFilePath($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height
                    ];
                }
            }
        }
        return $icons;
    }

    /**
     * Retrieves from the currently active quote the payment amount
     * @return float
     */
    protected function _getPaymentAmount()
    {
        return ((float) $this->_checkoutSession->getQuote()->getGrandTotal());
    }
}