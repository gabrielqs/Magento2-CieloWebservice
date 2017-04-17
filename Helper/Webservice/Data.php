<?php

namespace Gabrielqs\Cielo\Helper\Webservice;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Gabrielqs\Cielo\Model\Webservice;


class Data extends AbstractHelper
{
    /**
     * Store manager interface
     *
     * @var StoreManagerInterface $_storeManager
     */
    protected $_storeManager = null;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig = null;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->_scopeConfig  = $scopeConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Returns Cielo access key
     *
     * @return string $token
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAccessKey()
    {
        $return = null;
        if ($this->isTest()) {
            $return = $this->getConfigData('test_access_key');
        } else {
            $return = $this->getConfigData('access_key');
        }
        if (!$return) {
            throw new LocalizedException(__('Cielo access key not yet configured'));
        }
        return $return;
    }

    /**
     * Returns Cielo company id
     *
     * @return string $email
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCompanyId()
    {
        $return = null;
        if ($this->isTest()) {
            $return = $this->getConfigData('test_company_id');
        } else {
            $return = $this->getConfigData('company_id');
        }
        if (!$return) {
            throw new LocalizedException(__('Cielo company id not yet configured'));
        }
        return $return;
    }

    /**
     * Returns Cielo Payment Method System Config
     *
     * @param string $field
     * @param null $storeId
     * @return array|string
     */
    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->_storeManager->getStore(null);
        }
        $path = 'payment/' . $this->getMethodCode() . '/' . $field;
        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns Cielo Webservice Method Code
     * @return string
     */
    public function getMethodCode()
    {
        return Webservice::CODE;
    }

    /**
     * Are we in test mode?
     * @return bool
     */
    public function isAvsActive()
    {
        return (bool) $this->getConfigData('avs_mode_enabled');
    }

    /**
     * Are we in test mode?
     * @return bool
     */
    public function isTest()
    {
        return (bool) $this->getConfigData('test_mode_enabled');
    }
}