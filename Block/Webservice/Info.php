<?php

namespace Gabrielqs\Cielo\Block\Webservice;

use Magento\Payment\Block\Info\Cc as InfoCc;
use Magento\Framework\View\Element\Template\Context;
use \Magento\Payment\Model\Config as PaymentConfig;
use Gabrielqs\Cielo\Helper\Webservice\Data as WebserviceHelper;

class Info extends InfoCc
{
    /**
     * Webservice helper
     * @var WebserviceHelper
     */
    protected $_webserviceHelper = null;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PaymentConfig $paymentConfig
     * @param WebserviceHelper $webserviceHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentConfig $paymentConfig,
        WebserviceHelper $webserviceHelper,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $data);
        $this->_webserviceHelper = $webserviceHelper;
    }

    /**
     * Retrieves installment quantity from payment info
     * @return int
     */
    public function getInstallmentQuantity()
    {
        return (int) $this->getInfo()->getAdditionalInformation('installment_quantity');
    }

    /**
     * Retrieves interest rate from order
     * @return float
     */
    public function getOrderInterestRate()
    {
        return (float) $this->getInfo()->getOrder()->getGabrielqsInstallmentsInterestRate();
    }

    /**
     * Adds specific information to the info block
     * @return string[]
     */
    public function getSpecificInformation()
    {
        $return = parent::getSpecificInformation();

        $installmentQuantity = (int) $this->getInstallmentQuantity();
        if ($installmentQuantity <= 1) {
            $return[(string) __('Installment')] = (string) __('In cash');
        } else {

            $value = $installmentQuantity;
            $interestRate = $this->getOrderInterestRate();
            if ($interestRate <= 1) {
                $value .= (string) __(' times without interest');
            } else {
                $value .= (string) __(' times with interest');
            }
            $return[(string) __('Installment')] = $value;
        }


        return $return;
    }
}