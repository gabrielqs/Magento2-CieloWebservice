<?php

namespace Gabrielqs\Cielo\Model\System\Config;

use \Magento\Framework\Validator\Exception;
use \Magento\Framework\Phrase;
use \Magento\Framework\Message\Error;
use \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

/**
 * InstallmentsWithNoInterest Backend model. Serializes and unserializes installments array from config.
 *
 * Class InstallmentsWithNoInterest
 * @package Gabrielqs\Cielo\Model\System\Config
 */
class InstallmentsWithNoInterest extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{
    /**
     * Checks if one installment has been added more than once to the field and throws an exception if it did.
     *
     * @return ArraySerialized
     * @throws Exception
     */
    public function validateBeforeSave()
    {
        $options = (array) $this->getValue();
        $arrInstallments = [];
        $boolErrorDuplicate = false;
        $boolErrorOneInstallment = false;
        foreach ($options as $option) {
            if (is_array($option) && key_exists('installments', $option)) {
                $intInstallment = $option['installments'];

                if (((int) $intInstallment) === 1) {
                    $boolErrorOneInstallment = true;
                }

                if (in_array($intInstallment, $arrInstallments)) {
                    $boolErrorDuplicate = true;
                } else {
                    $arrInstallments[] = $intInstallment;
                }
            }
        }

        if ($boolErrorDuplicate || $boolErrorOneInstallment) {
            if ($boolErrorDuplicate) {
                $strMessage = "Installment selected more than once";
            } else {
                $strMessage = "Installment can't be 1";
            }
            $errors = [$strMessage];
            $exception = new Exception(
                new Phrase(implode(PHP_EOL, $errors))
            );
            foreach ($errors as $errorMessage) {
                $exception->addMessage(new Error($errorMessage));
            }
            throw $exception;
        }
        return parent::validateBeforeSave();
    }
}