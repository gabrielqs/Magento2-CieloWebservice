<?php

namespace Gabrielqs\Cielo\Test\Unit\Model\System\Config;

use \Gabrielqs\Cielo\Model\System\Config\InstallmentsWithNoInterest;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * InstallmentsWithNoInterest Backend model test
 */
class InstallmentsWithNoInterestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InstallmentsWithNoInterest $_backend
     */
    protected $_backend = null;

    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_backend = $objectManager->getObject('Gabrielqs\Cielo\Model\System\Config\InstallmentsWithNoInterest');
    }

    public function testNoDuplicateInstallments()
    {
        $this->setExpectedException('\Magento\Framework\Validator\Exception');

        $arrayTestDuplicateValues = [
            [
                'installments' => 2,
                'value' => 300
            ],
            [
                'installments' => 2,
                'value' => 300
            ],
        ];

        $this->_backend->setValue($arrayTestDuplicateValues);
        $this->_backend->validateBeforeSave();
    }

    public function testNo1InstallmentIsPassed()
    {
        $this->setExpectedException('\Magento\Framework\Validator\Exception');

        $arrayTestDuplicateValues = [
            [
                'installments' => 1,
                'value' => 300
            ]
        ];

        $this->_backend->setValue($arrayTestDuplicateValues);
        $this->_backend->validateBeforeSave();
    }
}