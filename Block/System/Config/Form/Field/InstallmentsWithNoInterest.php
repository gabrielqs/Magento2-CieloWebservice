<?php

namespace Gabrielqs\Cielo\Block\System\Config\Form\Field;

use \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use \Magento\Framework\DataObject;
use \Gabrielqs\Cielo\Block\Adminhtml\Form\Field\InstallmentsWithNoInterest as InstallmentsWithNoInterestRenderer;

/**
 * Responsible for creating a form field for the serialized arry option InstallmentsWithNoInterest
 *
 * Class InstallmentsWithNoInterest
 * @package Gabrielqs\Cielo\Block\System\Config\Form\Field
 */
class InstallmentsWithNoInterest extends AbstractFieldArray
{
    /**
     * Renderer for the installments selectbox
     *
     * @var InstallmentsWithNoInterestRenderer $_storeRender
     */
    protected $_storeRender;

    /**
     * Returns the installments selectbox renderer
     * @return InstallmentsWithNoInterestRenderer
     */
    protected function _getInstallmentsWithNoInterestRenderer()
    {
        if (!$this->_storeRender) {
            $this->_storeRender = $this->getLayout()->createBlock(
                'Gabrielqs\Cielo\Block\Adminhtml\Form\Field\InstallmentsWithNoInterest',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->_storeRender;
    }

    /**
     * Prepares line for rendering
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'installments',
            [
                'label' => __('Installments'),
                'class' => 'validate-no-empty',
                'renderer' => $this->_getInstallmentsWithNoInterestRenderer(),
            ]
        );
        $this->addColumn(
            'value',
            [
                'label' => __('Value'),
                'class' => 'validate-no-empty validate-decimal'
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare serialized array row to be shown
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow( DataObject $row )
    {
        $optionExtraAttr = [];
        $optionExtraAttr[
            'option_' . $this->_getInstallmentsWithNoInterestRenderer()->calcOptionHash($row->getData('installments'))
        ] = 'selected="selected"';
        $row->setData('option_extra_attrs', $optionExtraAttr);
    }
}