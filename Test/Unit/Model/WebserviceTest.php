<?php

namespace Gabrielqs\Cielo\Test\Unit\Model;

use Gabrielqs\Cielo\Helper\Webservice\Data as WebserviceHelper;
use Gabrielqs\Cielo\Model\Webservice;
use \Gabrielqs\Cielo\Model\Webservice\Api;
use \Gabrielqs\Cielo\Helper\Webservice\Installments as InstallmentsHelper;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Magento\Framework\DataObject;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Sales\Model\Order\Payment\Transaction;



/**
 * Webservice Test Case
 */
class WebserviceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Api
     */
    protected $api = null;

    /**
     * @var string
     */
    protected $className = null;

    /**
     * @var DataObject
     */
    protected $dataObject = null;

    /**
     * @var InstallmentsHelper
     */
    protected $installmentsHelper = null;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var Api
     */
    protected $originalSubject = null;

    /**
     * @var WebserviceHelper
     */
    protected $webserviceHelper = null;

    /**
     * @var Api
     */
    protected $subject = null;

    public function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->className = 'Gabrielqs\Cielo\Model\Webservice';

        $this->subject = $this
            ->getMockBuilder($this->className)
            ->setMethods(['getInfoInstance', '_getApi', 'canRefund', 'getConfigData'])
            ->setConstructorArgs($this->getConstructorArguments())
            ->getMock();

        $this->dataObject = $this
            ->getMockBuilder('\Magento\Framework\DataObject')
            ->setMethods(['getAdditionalData'])
            ->getMock();

        $this
            ->subject
            ->expects($this->any())
            ->method('_getApi')
            ->will($this->returnValue($this->api));

        $this->originalSubject = $this->objectManager->getObject($this->className);
    }

    protected function getConstructorArguments()
    {
        $arguments = $this->objectManager->getConstructArguments($this->className);

        $this->installmentsHelper = $this
            ->getMockBuilder(InstallmentsHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['setInstallmentDataBeforeAuthorization'])
            ->getMock();
        $arguments['installmentsHelper'] = $this->installmentsHelper;

        $this->webserviceHelper = $this
            ->getMockBuilder(WebserviceHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAccessKey', 'isAvsActive', 'getCompanyId', 'isTest'])
            ->getMock();
        $arguments['webserviceHelper'] = $this->webserviceHelper;

        $this->api = $this
            ->getMockBuilder('\Gabrielqs\Cielo\Model\Webservice\Api')
            ->disableOriginalConstructor()
            ->setMethods(['makeAuthRequest', 'makeCaptureRequest', 'makeRefundRequest'])
            ->getMock();
        $arguments['api'] = $this->api;

        return $arguments;
    }

    public function dataProvidertestAssignDataCorrectlyAssignsDataToInfoInstance()
    {
        return [
            [null, 1],
            [5, 5],
            [10, 10]
        ];
    }

    /**
     * @dataProvider dataProvidertestAssignDataCorrectlyAssignsDataToInfoInstance
     */
    public function testAssignDataCorrectlyAssignsDataToInfoInstance($input, $expectedStoredValue)
    {
        $payment = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this
            ->subject
            ->expects($this->any())
            ->method('getInfoInstance')
            ->will($this->returnValue($payment));

        $payment
            ->expects($this->once())
            ->method('setAdditionalInformation')
            ->with('installment_quantity', $expectedStoredValue);

        $this
            ->dataObject
            ->expects($this->any())
            ->method('getAdditionalData')
            ->with('installment_quantity')
            ->will($this->returnValue($input));

        $this
            ->installmentsHelper
            ->expects($this->once())
            ->method('setInstallmentDataBeforeAuthorization')
            ->with($expectedStoredValue);

        $this->dataObject->setAdditionalData(['installment_quantity', $input]);
        $this->subject->assignData($this->dataObject);
    }

    protected function _getTestAuthorizeTransactionAuthorizedMockXml()
    {
        return utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><transacao><status>' .
            Api::TRANSACTION_STATUS_AUTHORIZED.'</status><tid>102283030000ABC02</tid></transacao>');
    }

    public function testAuthorizeTransactionAuthorized()
    {
        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'setAmount',
                'setStatus',
                'setIsTransactionPending',
                'setCcTransId',
                'setTransactionId',
                'setCcNumber',
                'setCcCid',
                'setCcNumberEnc',
                'setTransactionAdditionalInfo',
                'addTransaction'
            ], [], '', false);

        $payment
            ->expects($this->any())
            ->method('setAmount')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setStatus')
            ->with(Webservice::STATUS_APPROVED)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setIsTransactionPending')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcTransId')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionId')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumber')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcCid')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumberEnc')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionAdditionalInfo')
            ->will($this->returnValue($payment));

        $apiReturn = new \SimpleXmlElement($this->_getTestAuthorizeTransactionAuthorizedMockXml());
        $this
            ->api
            ->expects($this->once())
            ->method('makeAuthRequest')
            ->with($payment, 18.20)
            ->will($this->returnValue($apiReturn));

        $this->subject->authorize($payment, 18.20);
    }

    protected function _getTestAuthorizeTransactionNonAuthorizedMockXml()
    {
        return utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><transacao><status>' .
            Api::TRANSACTION_STATUS_CANCELED.'</status><tid>102283030000ABC02</tid></transacao>');
    }

    public function testAuthorizeTransactionNonAuthorized()
    {
        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'setAmount',
                'setStatus',
                'setIsTransactionPending',
                'setCcNumber',
                'setCcCid',
                'setCcNumberEnc',
                'setTransactionAdditionalInfo'
            ], [], '', false);

        $payment
            ->expects($this->any())
            ->method('setAmount')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setStatus')
            ->with(Webservice::STATUS_DECLINED)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setIsTransactionPending')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumber')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcCid')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumberEnc')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionAdditionalInfo')
            ->will($this->returnValue($payment));

        $apiReturn = new \SimpleXmlElement($this->_getTestAuthorizeTransactionNonAuthorizedMockXml());
        $this
            ->api
            ->expects($this->once())
            ->method('makeAuthRequest')
            ->with($payment, 18.20)
            ->will($this->returnValue($apiReturn));

        $this->setExpectedException(LocalizedException::class);

        $this->subject->authorize($payment, 18.20);
    }

    protected function _getTestAuthorizeTransactionAuthorizedWithAvsMockXml()
    {
        return utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><transacao><status>' .
            Api::TRANSACTION_STATUS_AUTHORIZED.'</status><tid>102283030000ABC02</tid><autorizacao>' .
            '<mensagem-avs-cep>Foo Bar</mensagem-avs-cep><mensagem-avs-end>Foo Baz</mensagem-avs-end>' .
            '</autorizacao></transacao>');
    }

    public function testAuthorizeTransactionAuthorizedWithAvs()
    {
        $this
            ->webserviceHelper
            ->expects($this->any())
            ->method('isAvsActive')
            ->will($this->returnValue(true));

        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'setAmount',
                'setStatus',
                'setIsTransactionPending',
                'setCcTransId',
                'setTransactionId',
                'setCcNumber',
                'setCcCid',
                'setCcNumberEnc',
                'setTransactionAdditionalInfo',
                'addTransaction',
                'setAdditionalInformation'
            ], [], '', false);

        $payment
            ->expects($this->any())
            ->method('setAmount')
            ->with(18.20)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setStatus')
            ->with(Webservice::STATUS_APPROVED)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setIsTransactionPending')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcTransId')
            ->with('102283030000ABC02')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionId')
            ->with($this->stringContains('102283030000ABC02'))
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumber')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcCid')
            ->with(null)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumberEnc')
            ->with(null)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionAdditionalInfo')
            ->will($this->returnValue($payment));

        $payment
            ->expects($this->exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                ['cielo_avs_cep', 'Foo Bar'],
                ['cielo_avs_endereco', 'Foo Baz']
            );

        $apiReturn = new \SimpleXmlElement($this->_getTestAuthorizeTransactionAuthorizedWithAvsMockXml());
        $this
            ->api
            ->expects($this->once())
            ->method('makeAuthRequest')
            ->with($payment, 18.20)
            ->will($this->returnValue($apiReturn));

        $this->subject->authorize($payment, 18.20);
    }

    public function testAuthorizeUnexpectedReturn()
    {
        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [], [], '', false);

        $apiReturn = false;
        $this
            ->api
            ->expects($this->once())
            ->method('makeAuthRequest')
            ->with($payment, 18.20)
            ->will($this->returnValue($apiReturn));

        $this->setExpectedException(LocalizedException::class);

        $this->subject->authorize($payment, 18.20);
    }

    public function testCanUseForCurrencySupported()
    {
        $this->assertTrue($this->subject->canUseForCurrency('BRL'));
    }

    public function testCanUseForCurrencyUnsupported()
    {
        $this->assertNotTrue($this->subject->canUseForCurrency('USD'));
    }

    protected function _getTestCaptureTransactionCapturedSuccessfullyMockXml()
    {
        return utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><transacao><status>' .
            Api::TRANSACTION_STATUS_CAPTURED.'</status><tid>102283030000ABC02</tid></transacao>');
    }

    public function testCaptureTransactionCapturedSuccessfully()
    {
        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'getCcTransId',
                'setAmount',
                'setStatus',
                'setTransactionId',
                'setIsTransactionPending',
                'setCcNumber',
                'setCcCid',
                'setCcNumberEnc',
                'setTransactionAdditionalInfo'
            ], [], '', false);

        $payment
            ->expects($this->any())
            ->method('getCcTransId')
            ->will($this->returnValue('102283030000ABC02'));
        $payment
            ->expects($this->any())
            ->method('setAmount')
            ->with(158.20)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setStatus')
            ->with(Webservice::STATUS_SUCCESS)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionId')
            ->with('102283030000ABC02-capture')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setIsTransactionPending')
            ->with(false)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumber')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcCid')
            ->with(null)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumberEnc')
            ->with(null)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionAdditionalInfo')
            ->will($this->returnValue($payment));

        $apiReturn = simplexml_load_string($this->_getTestCaptureTransactionCapturedSuccessfullyMockXml());
        $this
            ->api
            ->expects($this->once())
            ->method('makeCaptureRequest')
            ->with($payment, 158.20)
            ->will($this->returnValue($apiReturn));

        $this->subject->capture($payment, 158.20);
    }

    protected function _getTestCaptureTransactionCapturedNotCapturedyMockXml()
    {
        return utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><transacao><status>' .
            Api::TRANSACTION_STATUS_CANCELED.'</status><tid>102283030000ABC02</tid></transacao>');
    }

    public function testCaptureTransactionCapturedNotCaptured()
    {
        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'getCcTransId',
                'setAmount',
                'setStatus',
                'setCcNumber',
                'setCcCid',
                'setCcNumberEnc',
                'setTransactionAdditionalInfo'
            ], [], '', false);

        $payment
            ->expects($this->any())
            ->method('getCcTransId')
            ->will($this->returnValue('102283030000ABC02'));
        $payment
            ->expects($this->any())
            ->method('setAmount')
            ->with(223.87)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setStatus')
            ->with(Webservice::STATUS_ERROR)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumber')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcCid')
            ->with(null)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setCcNumberEnc')
            ->with(null)
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionAdditionalInfo')
            ->will($this->returnValue($payment));

        $apiReturn = simplexml_load_string($this->_getTestCaptureTransactionCapturedNotCapturedyMockXml());
        $this
            ->api
            ->expects($this->once())
            ->method('makeCaptureRequest')
            ->with($payment, 223.87)
            ->will($this->returnValue($apiReturn));

        $this->setExpectedException(LocalizedException::class);
        $this->subject->capture($payment, 223.87);
    }


    public function testCaptureTransactionCapturedNoPreviousTransactionShouldThrowException()
    {
        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'getCcTransId'
            ], [], '', false);

        $payment
            ->expects($this->any())
            ->method('getCcTransId')
            ->will($this->returnValue(null));

        $this->setExpectedException(LocalizedException::class);
        $this->subject->capture($payment, 223.87);
    }

    public function testGetVerificationRegExHasCieloCcTypesRegexes()
    {
        $verificationRegexes = $this->originalSubject->getVerificationRegEx();

        $this->assertArrayHasKey('AU', $verificationRegexes);
        $this->assertArrayHasKey('EL', $verificationRegexes);
        $this->assertArrayHasKey('HI', $verificationRegexes);
    }

    protected function _getTestRefundTransactionRefundedSuccessfullyMockXml()
    {
        return utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><transacao><status>' .
            Api::TRANSACTION_STATUS_CANCELED.'</status><tid>102283030000ABC02</tid></transacao>');
    }

    public function testRefundTransactionRefundedSuccessfully()
    {
        $this
            ->subject
            ->expects($this->once())
            ->method('canRefund')
            ->will($this->returnValue(true));

        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'setTransactionId',
                'setTransactionAdditionalInfo',
                'addTransaction'
            ], [], '', false);

        $payment
            ->expects($this->any())
            ->method('setTransactionId')
            ->with('102283030000ABC02-refund')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('setTransactionAdditionalInfo')
            ->will($this->returnValue($payment));
        $payment
            ->expects($this->any())
            ->method('addTransaction')
            ->with(Transaction::TYPE_REFUND)
            ->will($this->returnValue($payment));

        $apiReturn = simplexml_load_string($this->_getTestRefundTransactionRefundedSuccessfullyMockXml());
        $this
            ->api
            ->expects($this->once())
            ->method('makeRefundRequest')
            ->with($payment, 200.00)
            ->will($this->returnValue($apiReturn));

        $this->subject->refund($payment, 200.00);
    }

    protected function _getTestRefundTransactionFailureMockXml()
    {
        return utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><transacao><status>' .
            Api::TRANSACTION_STATUS_CAPTURED.'</status><tid>102283030000ABC02</tid></transacao>');
    }

    public function testRefundTransactionFailureShouldThrowException()
    {
        $this
            ->subject
            ->expects($this->once())
            ->method('canRefund')
            ->will($this->returnValue(true));

        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [], [], '', false);

        $apiReturn = simplexml_load_string($this->_getTestRefundTransactionFailureMockXml());
        $this
            ->api
            ->expects($this->once())
            ->method('makeRefundRequest')
            ->with($payment, 200.00)
            ->will($this->returnValue($apiReturn));

        $this->setExpectedException(LocalizedException::class);
        $this->subject->refund($payment, 200.00);
    }

    public function testRefundTransactionCantRefundShouldThrowException()
    {
        $this
            ->subject
            ->expects($this->once())
            ->method('canRefund')
            ->will($this->returnValue(false));

        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [], [], '', false);

        $this->setExpectedException(LocalizedException::class);
        $this->subject->refund($payment, 200.00);
    }

    public function testIsAvailableShouldReturnFalseWhenMethodIsNotActive()
    {
        $this
            ->subject
            ->expects($this->once())
            ->method('getConfigData')
            ->with('active')
            ->will($this->returnValue(false));

        $this->assertNotTrue($this->subject->isAvailable());
    }

    public function testIsAvailableShouldReturnFalseWhenNoQuoteAvailable()
    {
        $this
            ->subject
            ->expects($this->once())
            ->method('getConfigData')
            ->with('active')
            ->will($this->returnValue(true));

        $quote = null;

        $this->assertNotTrue($this->subject->isAvailable($quote));
    }

    public function testIsAvailableShouldReturnFalseWhenGrandTotalIsLessThanMinimum()
    {
        $this
            ->subject
            ->expects($this->exactly(2))
            ->method('getConfigData')
            ->withConsecutive(
                ['active', null],
                ['min_order_total', null]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue(50)
            );

        $quote = $this
            ->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->setMethods(['getBaseGrandTotal'])
            ->getMock();
        $quote
            ->expects($this->once())
            ->method('getBaseGrandTotal')
            ->will($this->returnValue(20));

        $this->assertNotTrue($this->subject->isAvailable($quote));
    }

    public function testIsAvailableShouldReturnFalseWhenGrandTotalIsGreaterThanMaximumAndMaximumIsSet()
    {
        $this
            ->subject
            ->expects($this->exactly(4))
            ->method('getConfigData')
            ->withConsecutive(
                ['active', null],
                ['min_order_total', null],
                ['max_order_total', null],
                ['max_order_total', null]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue(5),
                $this->returnValue(10000),
                $this->returnValue(10000)
            );

        $quote = $this
            ->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->setMethods(['getBaseGrandTotal'])
            ->getMock();
        $quote
            ->expects($this->exactly(2))
            ->method('getBaseGrandTotal')
            ->will($this->returnValue(100000));

        $this->assertNotTrue($this->subject->isAvailable($quote));
    }

    public function testIsAvailableShouldReturnFalseWhenNotInTestModeAndConfigurationHasNotBeenMadeCompanyId()
    {
        $this
            ->subject
            ->expects($this->exactly(4))
            ->method('getConfigData')
            ->withConsecutive(
                ['active', null],
                ['min_order_total', null],
                ['max_order_total', null],
                ['max_order_total', null]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue(5),
                $this->returnValue(999999),
                $this->returnValue(999999)
            );

        $quote = $this
            ->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->setMethods(['getBaseGrandTotal'])
            ->getMock();
        $quote
            ->expects($this->exactly(2))
            ->method('getBaseGrandTotal')
            ->will($this->returnValue(1000));

        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('getCompanyId')
            ->will($this->returnValue(''));
        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('isTest')
            ->will($this->returnValue(false));

        $this->assertNotTrue($this->subject->isAvailable($quote));
    }

    public function testIsAvailableShouldReturnFalseWhenNotInTestModeAndConfigurationHasNotBeenMadeAccessKey()
    {
        $this
            ->subject
            ->expects($this->exactly(4))
            ->method('getConfigData')
            ->withConsecutive(
                ['active', null],
                ['min_order_total', null],
                ['max_order_total', null],
                ['max_order_total', null]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue(5),
                $this->returnValue(999999),
                $this->returnValue(999999)
            );

        $quote = $this
            ->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->setMethods(['getBaseGrandTotal'])
            ->getMock();
        $quote
            ->expects($this->exactly(2))
            ->method('getBaseGrandTotal')
            ->will($this->returnValue(1000));

        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('getCompanyId')
            ->will($this->returnValue('123123123'));
        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('getAccessKey')
            ->will($this->returnValue(''));
        $this
            ->webserviceHelper
            ->expects($this->once())
            ->method('isTest')
            ->will($this->returnValue(false));

        $this->assertNotTrue($this->subject->isAvailable($quote));
    }
}
