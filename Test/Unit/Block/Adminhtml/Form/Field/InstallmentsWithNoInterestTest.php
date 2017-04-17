<?php

namespace Gabrielqs\Cielo\Test\Unit\Block\Adminhtml\Form\Field;

use \Gabrielqs\Cielo\Block\Adminhtml\Form\Field\InstallmentsWithNoInterest;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * InstallmentWithNoInterest select box renderer Unit Testcase
 */
class InstallmentsWithNoInterestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var InstallmentsWithNoInterest $_block
     */
    protected $_block = null;

    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_block = $objectManager->getObject('Gabrielqs\Cielo\Block\Adminhtml\Form\Field\InstallmentsWithNoInterest');
    }

    public function testOutputIsSelect()
    {
        echo $this->_block->_toHtml();
        $this->expectOutputRegex('/\<select .+\>.+\<\/select\>/');
    }

    public function testOutputHasElevenEntries()
    {
        $output = $this->_block->_toHtml();
        $matches = [];
        preg_match_all('/\<option [a-zA-Z\=\s\"]+\>/', $output, $matches);
        $this->assertCount(11, $matches[0]);
    }

}