<?php

namespace Gabrielqs\Cielo\Test\Unit\Helper\Webservice;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Gabrielqs\Cielo\Helper\Webservice\Data as WebserviceHelper;
use \Magento\Framework\Exception\LocalizedException;

/**
 * DataTest, Webservice Helper Testcase
 */
class DataTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var WebserviceHelper
     */
    protected $helper = null;

    protected function setUp()
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = 'Gabrielqs\Cielo\Helper\Webservice\Data';
        $arguments = $objectManagerHelper->getConstructArguments($className);
        $this->helper = $this->getMock($className, ['getConfigData'], $arguments);
    }

    public function testIsTestRetrievesCorrectKey()
    {
        $this
            ->helper
            ->expects($this->once())
            ->method('getConfigData')
            ->with('test_mode_enabled')
            ->willReturn($this->returnValue(true));

        $this
            ->helper
            ->isTest();
    }

    public function testIsAvsActiveRetrievesCorrectKey()
    {
        $this
            ->helper
            ->expects($this->once())
            ->method('getConfigData')
            ->with('avs_mode_enabled')
            ->willReturn($this->returnValue(true));

        $this
            ->helper
            ->isAvsActive();
    }

    public function testReturnsCorrectMethodCode()
    {
        $this->assertEquals('cielo_webservice', $this->helper->getMethodCode());
    }

    public function testGetCompanyIdTestMode()
    {
        /*
         * Test mode enabled, should retrieve test_company_id
         */
        $this->helper
            ->expects($this->exactly(2))
            ->method('getConfigData')
            ->withConsecutive(
                [$this->equalTo('test_mode_enabled'), $this->equalTo(null)],
                [$this->equalTo('test_company_id'), $this->equalTo(null)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                'test_company_id'
            );

        $this
            ->helper
            ->getCompanyId();
    }

    public function testGetCompanyIdThrowsExceptionWhenItGetsAnEmptyValue()
    {
        $this->helper
            ->expects($this->exactly(2))
            ->method('getConfigData')
            ->withConsecutive(
                [$this->equalTo('test_mode_enabled'), $this->equalTo(null)],
                [$this->equalTo('company_id'), $this->equalTo(null)]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                ''
            );

        $this->setExpectedException(LocalizedException::class);

        $this
            ->helper
            ->getCompanyId();
    }

    public function testGetCompanyIdNonTestMode()
    {
        /*
         * Test mode disabled, should retrieve company_id path from config
         */
        $this->helper
            ->expects($this->exactly(2))
            ->method('getConfigData')
            ->withConsecutive(
                [$this->equalTo('test_mode_enabled'), $this->equalTo(null)],
                [$this->equalTo('company_id'), $this->equalTo(null)]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                'company_id'
            );

        $this
            ->helper
            ->getCompanyId();
    }

    public function testGetAccessKeyTestMode()
    {
        /*
         * Test mode enabled, should retrieve test_company_id
         */
        $this->helper
            ->expects($this->exactly(2))
            ->method('getConfigData')
            ->withConsecutive(
                [$this->equalTo('test_mode_enabled'), $this->equalTo(null)],
                [$this->equalTo('test_access_key'), $this->equalTo(null)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                'test_key'
            );

        $this
            ->helper
            ->getAccessKey();
    }

    public function testGetAccessKeyNonTestMode()
    {
        /*
         * Test mode disabled, should retrieve company_id path from config
         */
        $this->helper
            ->expects($this->exactly(2))
            ->method('getConfigData')
            ->withConsecutive(
                [$this->equalTo('test_mode_enabled'), $this->equalTo(null)],
                [$this->equalTo('access_key'), $this->equalTo(null)]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                'production_key'
            );

        $this
            ->helper
            ->getAccessKey();
    }

    public function testGetAccessKeyThrowsExceptionWhenItGetsAnEmptyValue()
    {
        $this->helper
            ->expects($this->exactly(2))
            ->method('getConfigData')
            ->withConsecutive(
                [$this->equalTo('test_mode_enabled'), $this->equalTo(null)],
                [$this->equalTo('access_key'), $this->equalTo(null)]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                ''
            );

        $this->setExpectedException(LocalizedException::class);

        $this
            ->helper
            ->getAccessKey();
    }
}

