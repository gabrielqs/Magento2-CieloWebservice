<?php
namespace Gabrielqs\Cielo\Model\Source;

/**
 * Class Cctype - Returns all available Credit card types for the Cielo Payment method
 * @package Gabrielqs\Cielo\Model\Source
 */
class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * Allowed CC Types for Cielo Payment methods
     *
     * @return array
     */
    public function getAllowedTypes()
    {
        return [
            'AE',
            'AU',
            'DI',
            'DN',
            'EL',
            'JC',
            'MC',
            'VI',
        ];
    }
}