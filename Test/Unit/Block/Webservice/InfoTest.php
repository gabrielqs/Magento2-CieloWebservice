<?php

namespace Gabrielqs\Cielo\Test\Unit\Block\Webservice;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Payment\Model\Config as PaymentConfig;
use \Gabrielqs\Cielo\Helper\Webservice\Data as WebserviceHelper;
use \Gabrielqs\Cielo\Block\Webservice\Info;
use \Gabrielqs\Installments\Model\QuoteManager;

/**
 * Webservice Info Block Unit Testcase
 */
class InfoTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var String
     */
    protected $className = null;

    /**
     * @var Context
     */
    protected $context = null;

    /**
     * @var QuoteManager
     */
    protected $objectManager = null;

    /**
     * @var PaymentConfig
     */
    protected $_paymentConfig = null;

    /**
     * @var Info
     */
    protected $subject = null;

    /**
     * @var WebserviceHelper
     */
    protected $webserviceHelper = null;



    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->className = '\Gabrielqs\Cielo\Block\Webservice\Info';

        $arguments = $this->getConstructorArguments();

        $this->subject = $this->objectManager->getObject($this->className, $arguments);
    }

    protected function getConstructorArguments()
    {
        $arguments = $this->objectManager->getConstructArguments($this->className);

        $this->context = $this
            ->getMockBuilder('\Magento\Framework\View\Element\Template\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $this->_paymentConfig = $this
            ->getMockBuilder('\Magento\Payment\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->webserviceHelper = $this
            ->getMockBuilder('\Gabrielqs\Cielo\Helper\Webservice\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $arguments['context'] = $this->context;
        $arguments['paymentConfig'] = $this->_paymentConfig;
        $arguments['webserviceHelper'] = $this->webserviceHelper;

        return $arguments;
    }

    /**
     *
     */
    public function dataProviderGetSpecificInformationAddsInterestInfoCorrectly()
    {
        return [
            [1, 1, 'In cash'],
            [3, 1, '3 times without interest'],
            [5, 1.0199, '5 times with interest'],
        ];
    }

    /**
     * @param $installmentQuantity
     * @param $orderInterestRate
     * @param $expectedInfo
     * @dataProvider dataProviderGetSpecificInformationAddsInterestInfoCorrectly
     */
    public function testGetSpecificInformationAddsInterestInfoCorrectly
        ($installmentQuantity, $orderInterestRate, $expectedInfo)
    {
        $mock = $this
            ->getMockBuilder('Gabrielqs\Cielo\Block\Webservice\Info')
            ->setMethods(['getInstallmentQuantity', 'getOrderInterestRate', 'getInfo'])
            ->setConstructorArgs($this->getConstructorArguments())
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('getInstallmentQuantity')
            ->will($this->returnValue($installmentQuantity));

        $mock
            ->expects($this->any())
            ->method('getInfo')
            ->will($this->returnValue($this->objectManager->getObject('\Magento\Framework\DataObject')));

        $mock
            ->expects($this->any())
            ->method('getOrderInterestRate')
            ->will($this->returnValue($orderInterestRate));

        $specificInfo = $mock->getSpecificInformation();

        $this->assertEquals($expectedInfo, $specificInfo['Installment']);
    }

}