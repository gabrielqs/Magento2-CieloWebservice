<?php

namespace Gabrielqs\Cielo\Block\Adminhtml\Form\Field;

/**
 * InstallmentWithNoInterest select box renderer
 *
 * Class InstallmentsWithNoInterest
 * @method InstallmentsWithNoInterest setName(string $value)
 * @package Gabrielqs\Cielo\Block\Adminhtml\Form\Field
 */
class InstallmentsWithNoInterest extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * Returns installment options for parent block to render
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $options = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            foreach ($options as $option) {
                $this->addOption($option, $option);
            }
        }
        return parent::_toHtml();
    }

    /**
     * Sets input name
     *
     * @param string $value
     * @return InstallmentsWithNoInterest
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}