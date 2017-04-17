<?php

namespace Gabrielqs\Cielo\Helper\Webservice;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Gabrielqs\Cielo\Helper\Webservice\Data as WebserviceHelper;
use \Gabrielqs\Installments\Model\Calculator;
use \Gabrielqs\Installments\Model\QuoteManager;

/**
 * Installments Helper
 * @package Gabrielqs\Cielo\Helper\Webservice
 */
class Installments extends AbstractHelper
{
    /**
     * Installments Calculator
     * @var Calculator
     */
    protected $_installmentsCalculator = null;

    /**
     * Installments Calculator Initialized Flag
     * @var bool
     */
    protected $_installmentsCalculatorInitialized = false;

    /**
     * Webservice Helper
     * @var WebserviceHelper
     */
    protected $_webserviceHelper = false;

    /**
     * InstallmentsQuoteManager
     * @var QuoteManager
     */
    protected $_installmentsQuoteManager = false;

    /**
     * Data constructor.
     * @param Context $context
     * @param WebserviceHelper $webserviceHelper
     * @param Calculator $installmentsCalculator
     * @param QuoteManager $installmentsQuoteManager
     */
    public function __construct(
        Context $context,
        WebserviceHelper $webserviceHelper,
        Calculator $installmentsCalculator,
        QuoteManager $installmentsQuoteManager
    ) {
        $this->_installmentsQuoteManager = $installmentsQuoteManager;
        $this->_installmentsCalculator = $installmentsCalculator;
        $this->_webserviceHelper = $webserviceHelper;
        parent::__construct($context);
    }

    /**
     * Gets installments config using the Installments Calculator
     * @see Calculator
     * @return \Magento\Framework\DataObject
     */
    public function getInstallmentConfig()
    {
        return $this->getInstallmentsCalculator()->getInstallmentConfig();
    }

    /**
     * Gets installments for a given value using the Installments Calculator
     * @param float $amount
     * @see Calculator
     * @return \Magento\Framework\DataObject[]
     */
    public function getInstallments($amount)
    {
        return $this->getInstallmentsCalculator()->getInstallments((float) $amount);
    }

    /**
     * Sets Interest configuration to the installment calculator
     * @return Calculator
     */
    public function getInstallmentsCalculator()
    {
        if (!$this->_isInstallmentsCalculatorInitialized()) {
            $this->_installmentsCalculator->setInterestRate($this->getInterestRate());
            $this->_installmentsCalculator->setMinimumInstallmentAmount($this->getMinimumInstallmentAmount());
            $this->_installmentsCalculator
                ->setMaximumInstallmentQuantity($this->getMaximumInstallmentQuantity());
            $this->_installmentsCalculator
                ->setMinimumAmountNoInterest($this->getMinimumOrderValueNoInterest());
            $this->_installmentsCalculatorInitialized = true;
        }
        return $this->_installmentsCalculator;
    }

    /**
     * Sets Interest configuration to the installment calculator
     * @return QuoteManager
     */
    public function getInstallmentsQuoteManager()
    {
        $this->_installmentsQuoteManager->setCalculator($this->getInstallmentsCalculator());
        return $this->_installmentsQuoteManager;
    }

    /**
     * Retrieves the interest rate from config
     * @return float
     */
    public function getInterestRate()
    {
        return (1 + ((float) $this->_webserviceHelper->getConfigData('interest_rate') / 100));
    }

    /**
     * Maximum Installment Quantity
     * @return int
     */
    public function getMaximumInstallmentQuantity()
    {
        $maximumInstallmentQuantity = (int) $this->_webserviceHelper->getConfigData('maximum_installment_quantity');
        if ($maximumInstallmentQuantity == 0) {
            $maximumInstallmentQuantity = 1;
        }
        return $maximumInstallmentQuantity;
    }

    /**
     * Minimum Installment Amount
     * @return float
     */
    public function getMinimumInstallmentAmount()
    {
        $minimumInstallmentAmount = (float) $this->_webserviceHelper->getConfigData('minimum_installment_value');
        if ($minimumInstallmentAmount < 5) {
            $minimumInstallmentAmount = 5;
        }
        return $minimumInstallmentAmount;
    }

    /**
     * Gets the minimum amount for which, given a installment qty, no interest should apply.
     * This method retrieves the values array from the config, sorts it and arranges it in an easier manner to be
     * read by other classes. It returns an array of the form array[$installmentQty] => $minOrderNoInterest
     * @return float[]
     */
    public function getMinimumOrderValueNoInterest()
    {
        $minOrderValuesConfig = $this->_webserviceHelper->getConfigData('minimum_order_value_no_interest');
        $minOrderValuesConfigArray = (array) @unserialize($minOrderValuesConfig);

        # Sorting the configuration array
        $sortedMinOrderValues = [];
        foreach ($minOrderValuesConfigArray as $minOrderCfgOption) {
            $installmentsCfgOption = (int) $minOrderCfgOption['installments'];
            $valueCfgOption = (float) $minOrderCfgOption['value'];

            if ($installmentsCfgOption <= 1) {
                continue;
            }

            $sortedMinOrderValues[$installmentsCfgOption] = $valueCfgOption;
        }

        return $sortedMinOrderValues;
    }

    /**
     * Getter for _installmentsCalculatorInitialized
     * @return bool
     */
    protected function _isInstallmentsCalculatorInitialized()
    {
        return $this->_installmentsCalculatorInitialized;
    }

    /**
     * Sets interest info on order object with help from the installments order manager class
     * @param int $installmentQuantity
     * @see QuoteManager
     * @return void
     */
    public function setInstallmentDataBeforeAuthorization($installmentQuantity)
    {
        $this->getInstallmentsQuoteManager()->setInstallmentDataBeforeAuthorization($installmentQuantity);
    }
}