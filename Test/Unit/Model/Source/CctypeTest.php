<?php

namespace Gabrielqs\Cielo\Test\Unit\Model\Source;

use \Gabrielqs\Cielo\Model\Source\Cctype;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * CcType Test Case
 */
class CctypeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Cctype $_config
     */
    protected $_config = null;

    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_config = $objectManager->getObject('Gabrielqs\Cielo\Model\Source\Cctype');
    }

    public function testTypesIsArray()
    {
        $this->assertInternalType('array', $this->_config->getAllowedTypes());
    }

    public function testAlllowedTypesIsNotEmpty()
    {
        $this->assertNotEmpty($this->_config->getAllowedTypes());
    }


    public function testToOptionArrayValid()
    {
        $expects = ['AE', 'AU', 'DI', 'DN', 'EL', 'JC', 'MC', 'VI'];
        $this->assertEquals($expects, $this->_config->getAllowedTypes());
    }
}