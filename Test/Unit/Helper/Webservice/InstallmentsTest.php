<?php

namespace Gabrielqs\Cielo\Test\Unit\Helper\Webservice;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Gabrielqs\Cielo\Helper\Webservice\Installments as InstallmentsHelper;
use \Gabrielqs\Cielo\Helper\Webservice\Data as WebserviceHelper;
use Gabrielqs\Installments\Model\QuoteManager;
use Gabrielqs\Installments\Model\Calculator;

/**
 * Installments Helper Unit Testcase
 */
class InstallmentsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Calculator
     */
    protected $calculator = null;


    /**
     * @var String
     */
    protected $className = null;

    /**
     * @var QuoteManager
     */
    protected $quoteManager = null;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var InstallmentsHelper
     */
    protected $subject = null;

    /**
     * @var WebserviceHelper Mock
     */
    protected $webserviceHelper = null;


    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->className = '\Gabrielqs\Cielo\Helper\Webservice\Installments';

        $arguments = $this->getConstructorArguments();

        $this->subject = $this->objectManager->getObject($this->className, $arguments);
    }

    protected function getConstructorArguments()
    {

        $arguments = $this->objectManager->getConstructArguments($this->className);

        $this->webserviceHelper = $this
            ->getMockBuilder('Gabrielqs\Cielo\Helper\Webservice\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $this->calculator = $this
            ->getMockBuilder('Gabrielqs\Installments\Model\Calculator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteManager = $this
            ->getMockBuilder('Gabrielqs\Installments\Model\QuoteManager')
            ->disableOriginalConstructor()
            ->getMock();

        $arguments['webserviceHelper'] = $this->webserviceHelper;
        $arguments['installmentsCalculator'] = $this->calculator;
        $arguments['installmentsQuoteManager'] = $this->quoteManager;

        return $arguments;
    }

    public function testGetInstallmentConfigGetsItFromCalculator()
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('getInstallmentsCalculator')
            ->will($this->returnValue($this->calculator));

        $this
            ->calculator
            ->expects($this->once())
            ->method('getInstallmentConfig');

        $mock->getInstallmentConfig();
    }

    public function testGetInstallmentsGetsItFromCalculatorWithCorrectValue()
    {
        $value = 200.51;

        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('getInstallmentsCalculator')
            ->will($this->returnValue($this->calculator));

        $this
            ->calculator
            ->expects($this->once())
            ->method('getInstallments')
            ->with($this->equalTo($value));

        $mock->getInstallments($value);
    }

    public function testGetInstallmentsCalculatorInitializesCalculator()
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(
                [
                    'getInterestRate',
                    'getMinimumInstallmentAmount',
                    'getMaximumInstallmentQuantity',
                    'getMinimumOrderValueNoInterest',
                    '_isInstallmentsCalculatorInitialized'
                ]
            )
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('_isInstallmentsCalculatorInitialized')
            ->will($this->returnValue(false));

        $mock
            ->expects($this->once())
            ->method('getInterestRate');

        $mock
            ->expects($this->once())
            ->method('getMinimumInstallmentAmount');

        $mock
            ->expects($this->once())
            ->method('getMaximumInstallmentQuantity');

        $mock
            ->expects($this->once())
            ->method('getMinimumOrderValueNoInterest');

        $mock
            ->getInstallmentsCalculator();
    }

    /**
     *
     */
    public function testGetInstallmentsCalculatorDoesntInitializeCalculatorTwice()
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(
                [
                    'getInterestRate',
                    'getMinimumInstallmentAmount',
                    'getMaximumInstallmentQuantity',
                    'getMinimumOrderValueNoInterest',
                    '_isInstallmentsCalculatorInitialized'
                ]
            )
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('_isInstallmentsCalculatorInitialized')
            ->will($this->returnValue(true));

        $mock
            ->expects($this->never())
            ->method('getInterestRate');

        $mock
            ->expects($this->never())
            ->method('getMinimumInstallmentAmount');

        $mock
            ->expects($this->never())
            ->method('getMaximumInstallmentQuantity');

        $mock
            ->expects($this->never())
            ->method('getMinimumOrderValueNoInterest');

        $mock
            ->getInstallmentsCalculator();
    }

    /**
     *
     */
    public function testGetInstallmentConfigCallsCalculatorObject()
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('getInstallmentsCalculator')
            ->will($this->returnValue($this->calculator));


        $mock
            ->getInstallmentsCalculator();
    }

    /**
     *
     */
    public function testGetInstallmentsQuoteManagerCorrectlyInitializesCalculatorAndQuoteManager()
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $this
            ->quoteManager
            ->expects($this->once())
            ->method('setCalculator');

        $mock
            ->expects($this->once())
            ->method('getInstallmentsCalculator')
            ->will($this->returnValue($this->calculator));

        $mock
            ->getInstallmentsQuoteManager();
    }

    /**
     * @return array
     */
    public function interestRateDataProvider()
    {
        return [
            [0, 1],
            [50, 1.5],
            [1.99, 1.0199],
            [0.99, 1.0099],
        ];
    }

    /**
     * @param $configValue
     * @param $expectedInterestRate
     * @dataProvider interestRateDataProvider
     */
    public function testGetInterestRateComputesCorrectRatesAfterConfig($configValue, $expectedInterestRate)
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('getConfigData')
            ->with($this->equalTo('interest_rate'), $this->equalTo(null))
            ->willReturn($configValue);

        $this->assertEquals($expectedInterestRate, $mock->getInterestRate());
    }

    /**
     * @return array
     */
    public function maximumInstallmentQuantityDataProvider()
    {
        return [
            [0, 1],
            [1, 1],
            [12, 12]
        ];
    }

    /**
     * @param $configValue
     * @param $expectedMaximumInstallmentQuantity
     * @dataProvider maximumInstallmentQuantityDataProvider
     */
    public function testGetMaximumInstallmentQuantityGetsItCorrectlyFromConfig
        ($configValue, $expectedMaximumInstallmentQuantity)
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('getConfigData')
            ->with($this->equalTo('maximum_installment_quantity'), $this->equalTo(null))
            ->willReturn($configValue);

        $this->assertEquals($expectedMaximumInstallmentQuantity, $mock->getMaximumInstallmentQuantity());
    }

    /**
     * @return array
     */
    public function minimumInstallmentAmountDataProvider()
    {
        return [
            [0, 5],
            [null, 5],
            ['', 5],
            [5, 5],
            [10, 10]
        ];
    }

    /**
     * @param $configValue
     * @param $expectedMaximumInstallmentQuantity
     * @dataProvider minimumInstallmentAmountDataProvider
     */
    public function testGetMinimumInstallmentAmountGetsItCorrectlyFromConfig
        ($configValue, $expectedMaximumInstallmentQuantity)
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('getConfigData')
            ->with($this->equalTo('minimum_installment_value'), $this->equalTo(null))
            ->willReturn($configValue);

        $this->assertEquals($expectedMaximumInstallmentQuantity, $mock->getMinimumInstallmentAmount());
    }

    /**
     * @return array
     */
    public function minimumOrderValueNoInterestDataProvider()
    {
        return [
            ['', []],

            [serialize([]), []],

            [
                serialize([
                    ['installments' => 2, 'value' => 200],
                    ['installments' => 3, 'value' => 300],
                    ['installments' => 7, 'value' => 700]
                ]),
                [
                    2 => 200,
                    3 => 300,
                    7 => 700
                ],
            ],

            [
                serialize([
                    ['installments' => 7, 'value' => 700],
                    ['installments' => 2, 'value' => 200],
                    ['installments' => 3, 'value' => 300]
                ]),
                [
                    2 => 200,
                    3 => 300,
                    7 => 700
                ]
            ],

            [
                serialize([
                    ['installments' => 1, 'value' => 700],
                    ['installments' => 0, 'value' => 200],
                    ['installments' => 3, 'value' => 300]
                ]),
                [
                    3 => 300
                ]
            ],
        ];
    }

    /**
     * @param $configValue
     * @param $expectedMaximumInstallmentQuantity
     * @dataProvider minimumOrderValueNoInterestDataProvider
     */
    public function testGetminimumOrderValueNoInterestDataProviderGetsItCorrectlyFromConfig
    ($configValue, $expectedMaximumInstallmentQuantity)
    {
        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('getConfigData')
            ->with($this->equalTo('minimum_order_value_no_interest'), $this->equalTo(null))
            ->willReturn($configValue);

        $this->assertEquals($expectedMaximumInstallmentQuantity, $mock->getMinimumOrderValueNoInterest());
    }

    /**
     *
     */
    public function testSetInstallmentDataBeforeAuthorization()
    {
        $value = 199.92;

        $mock = $this
            ->getMockBuilder($this->className)
            ->setConstructorArgs($this->getConstructorArguments())
            ->setMethods(['getInstallmentsCalculator'])
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('getInstallmentsCalculator')
            ->will($this->returnValue($this->calculator));

        $this
            ->quoteManager
            ->expects($this->once())
            ->method('setInstallmentDataBeforeAuthorization')
            ->with($this->equalTo($value));

        $mock->setInstallmentDataBeforeAuthorization($value);
    }
}

