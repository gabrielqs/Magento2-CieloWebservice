<?php

namespace Gabrielqs\Cielo\Test\Unit\Model\Webservice;

use \Gabrielqs\Cielo\Model\Webservice\Api;
use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Magento\Framework\Exception\LocalizedException;

/**
 * Api Test Case
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test access key
     */
    const TEST_ACCESS_KEY = '25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f3';

    /**
     * Test company id
     */
    const TEST_COMPANY_ID = '1006993069';

    /**
     * Test access key
     */
    const PROD_ACCESS_KEY = '25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f4';

    /**
     * Test company id
     */
    const PROD_COMPANY_ID = '1006993070';

    /**
     * @var string
     */
    protected $className = null;

    /**
     * @var \ReflectionMethod
     */
    protected $convertCardTypeMethod = null;

    /**
     * @var \ReflectionMethod
     */
    protected $getRootElement = null;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var Api
     */
    protected $originalSubject = null;

    /**
     * @var Api
     */
    protected $subject = null;

    public function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->className = 'Gabrielqs\Cielo\Model\Webservice\Api';
        $this->subject = $this
            ->getMockBuilder($this->className)
            ->setMethods(['_makeRequest', '_getRootElementOpeningTag', '_getDateTime'])
            ->setConstructorArgs($this->getConstructorArguments())
            ->getMock();

        $this->originalSubject = $this->objectManager->getObject($this->className);
        $reflection = new \ReflectionClass($this->objectManager->getObject($this->className));
        $this->convertCardTypeMethod = $reflection->getMethod('_convertCcTypeToCieloApi');
        $this->convertCardTypeMethod->setAccessible(true);
        $this->getRootElementMethod = $reflection->getMethod('_getRootElementOpeningTag');
        $this->getRootElementMethod->setAccessible(true);
    }

    protected function getConstructorArguments()
    {
        $arguments = $this->objectManager->getConstructArguments($this->className);
        return $arguments;
    }

    public function _getTestMakeAuthRequestTestModeNoAvsInstallmentXmlFormat()
    {
        return '<?xml version="1.0" encoding="ISO-8859-1" ?><requisicao-transacao ' .
             'id="6105d57e92d0c77293491b80ec605289" versao="1.2.1"><dados-ec><numero>' . self::TEST_COMPANY_ID .
             '</numero><chave>' . self::TEST_ACCESS_KEY . '</chave></dados-ec>' .
             '<dados-portador><numero>4444333322221111</numero><validade>201905</validade><indicador>1</indicador>' .
             '<codigo-seguranca>123</codigo-seguranca><nome-portador>João Silva</nome-portador></dados-portador>' .
             '<dados-pedido><numero>000000076</numero><valor>20034</valor><moeda>986</moeda>' .
             '<data-hora>2016-08-17T12:55:55</data-hora></dados-pedido>' .
             '<forma-pagamento><bandeira>visa</bandeira><produto>2</produto><parcelas>3</parcelas></forma-pagamento>' .
             '<url-retorno>null</url-retorno><autorizar>3</autorizar><capturar>false</capturar></requisicao-transacao>';
    }

    public function testMakeAuthRequestTestModeNoAvsInstallmentXmlFormat()
    {
        $order = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->setMethods([
                'getCustomerTaxvat',
                'getIncrementId',
                'getBillingAddress'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $order
            ->expects($this->once())
            ->method('getIncrementId')
            ->will($this->returnValue('000000076'));

        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'getOrder',
                'getAdditionalInformation',
                'getCcNumber',
                'getCcExpYear',
                'getCcExpMonth',
                'getCcCid',
                'getCcOwner',
                'getCcType'
            ], [], '', false);

        $payment
            ->expects($this->exactly(2))
            ->method('getOrder')
            ->will($this->returnValue($order));

        $payment
            ->expects($this->once())
            ->method('getAdditionalInformation')
            ->with('installment_quantity')
            ->will($this->returnValue(3));

        $payment
            ->expects($this->once())
            ->method('getCcNumber')
            ->will($this->returnValue('4444333322221111'));

        $payment
            ->expects($this->once())
            ->method('getCcExpYear')
            ->will($this->returnValue('2019'));

        $payment
            ->expects($this->once())
            ->method('getCcExpMonth')
            ->will($this->returnValue('5'));

        $payment
            ->expects($this->once())
            ->method('getCcCid')
            ->will($this->returnValue(123));

        $payment
            ->expects($this->once())
            ->method('getCcOwner')
            ->will($this->returnValue('João Silva'));

        $payment
            ->expects($this->once())
            ->method('getCcType')
            ->will($this->returnValue('VI'));

        $this
            ->subject
            ->expects($this->once())
            ->method('_makeRequest')
            ->with('https://qasecommerce.cielo.com.br/servicos/ecommwsec.do',
                $this->_getTestMakeAuthRequestTestModeNoAvsInstallmentXmlFormat())
            ->will($this->returnValue(utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><xml-teste><teste1>' .
                   '<teste2>asdf</teste2></teste1><teste3><teste4>laga laga</teste4></teste3></xml-teste>')));
        $this
            ->subject
            ->expects($this->once())
            ->method('_getRootElementOpeningTag')
            ->will($this->returnValue('<requisicao-transacao id="6105d57e92d0c77293491b80ec605289" versao="1.2.1">'));
        $this
            ->subject
            ->expects($this->once())
            ->method('_getDateTime')
            ->will($this->returnValue('2016-08-17T12:55:55'));

        $this->subject->setCompanyId(self::TEST_COMPANY_ID);
        $this->subject->setAccessKey(self::TEST_ACCESS_KEY);
        $this->subject->setAvsActive(false);
        $this->subject->setTestMode(true);


        $return = $this->subject->makeAuthRequest($payment, 200.34);
        $this->assertInstanceOf('SimpleXMLElement', $return);
    }

    protected function _getTestMakeAuthRequestNonTestModeWithAvsOnCashXmlFormatRequestXml()
    {
        return '<?xml version="1.0" encoding="ISO-8859-1" ?><requisicao-transacao' .
            ' id="6105d57e92d0c77293491b80ec605289" versao="1.2.1"><dados-ec><numero>' . self::PROD_COMPANY_ID .
            '</numero><chave>' . self::PROD_ACCESS_KEY . '</chave></dados-ec>' .
            '<dados-portador><numero>5453010000066167</numero><validade>202112</validade><indicador>1</indicador>' .
            '<codigo-seguranca>123</codigo-seguranca><nome-portador>Maria Martins</nome-portador>' .
            '<cnpj-cpf-portador>72100303520</cnpj-cpf-portador><tipo-pessoa>F</tipo-pessoa></dados-portador>' .
            '<dados-pedido><numero>100030079</numero><valor>1248</valor><moeda>986</moeda>' .
            '<data-hora>2016-08-17T12:55:55</data-hora></dados-pedido>' .
            '<forma-pagamento><bandeira>mastercard</bandeira><produto>1</produto><parcelas>1</parcelas>' .
            '</forma-pagamento><url-retorno>null</url-retorno><autorizar>3</autorizar><capturar>false</capturar>' .
            '<avs><![CDATA[<dados-avs><endereco>SQN 413 Bloco F</endereco><numero>105</numero>' .
            '<complemento>Residencial</complemento><bairro>Asa Norte</bairro><cep>70876-060</cep>' .
            '</dados-avs>]]></avs></requisicao-transacao>';
    }

    public function testMakeAuthRequestNonTestModeWithAvsOnCashXmlFormat()
    {
        $billingAddress = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Address')
            ->setMethods([
                'getPostcode',
                'getStreetLine'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $billingAddress
            ->expects($this->exactly(5))
            ->method('getStreetLine')
            ->withConsecutive([1], [2], [3], [3], [4])
            ->willReturnOnConsecutiveCalls(
                'SQN 413 Bloco F',
                '105',
                'Residencial',
                'Residencial',
                'Asa Norte'
            );

        $billingAddress
            ->expects($this->once())
            ->method('getPostcode')
            ->will($this->returnValue('70876-060'));

        $order = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->setMethods([
                'getCustomerTaxvat',
                'getIncrementId',
                'getBillingAddress'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $order
            ->expects($this->once())
            ->method('getIncrementId')
            ->will($this->returnValue('100030079'));

        $order
            ->expects($this->exactly(2))
            ->method('getCustomerTaxvat')
            ->will($this->returnValue('72100303520'));

        $order
            ->expects($this->once())
            ->method('getBillingAddress')
            ->will($this->returnValue($billingAddress));

        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'getOrder',
                'getAdditionalInformation',
                'getCcNumber',
                'getCcExpYear',
                'getCcExpMonth',
                'getCcCid',
                'getCcOwner',
                'getCcType'
            ], [], '', false);

        $payment
            ->expects($this->exactly(2))->method('getOrder')->will($this->returnValue($order));

        $payment
            ->expects($this->once())
            ->method('getAdditionalInformation')
            ->with('installment_quantity')
            ->will($this->returnValue(1));

        $payment
            ->expects($this->once())
            ->method('getCcNumber')
            ->will($this->returnValue('5453010000066167'));

        $payment
            ->expects($this->once())
            ->method('getCcExpYear')
            ->will($this->returnValue('2021'));

        $payment
            ->expects($this->once())
            ->method('getCcExpMonth')
            ->will($this->returnValue('12'));

        $payment
            ->expects($this->once())
            ->method('getCcCid')
            ->will($this->returnValue(123));

        $payment
            ->expects($this->once())
            ->method('getCcOwner')
            ->will($this->returnValue('Maria Martins'));

        $payment
            ->expects($this->once())
            ->method('getCcType')
            ->will($this->returnValue('MC'));

        $this
            ->subject
            ->expects($this->once())
            ->method('_getRootElementOpeningTag')
            ->will($this->returnValue('<requisicao-transacao id="6105d57e92d0c77293491b80ec605289" versao="1.2.1">'));
        $this
            ->subject
            ->expects($this->once())
            ->method('_getDateTime')
            ->will($this->returnValue('2016-08-17T12:55:55'));

        $returnXml = $this->_getTestMakeAuthRequestNonTestModeWithAvsOnCashXmlFormatRequestXml();
        $this
            ->subject
            ->expects($this->once())
            ->method('_makeRequest')
            ->with('https://ecommerce.cielo.com.br/servicos/ecommwsec.do', $returnXml)
            ->will($this->returnValue(utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><xml-teste><teste1>' .
                '<teste2>asdf</teste2></teste1><teste3><teste4>laga laga</teste4></teste3></xml-teste>')));

        $this->subject->setCompanyId(self::PROD_COMPANY_ID);
        $this->subject->setAccessKey(self::PROD_ACCESS_KEY);
        $this->subject->setAvsActive(true);
        $this->subject->setTestMode(false);

        $return = $this->subject->makeAuthRequest($payment, 12.48);
        $this->assertInstanceOf('SimpleXMLElement', $return);
    }

    protected function _getTestMakeCaptureRequestXmlFormatRequestXml()
    {
        return '<?xml version="1.0" encoding="ISO-8859-1" ?>' . "\n" .
            '<requisicao-captura id="6105d57e92d0c77293491b80ec605289" versao="1.2.1"><tid>19239123000ACD0</tid>' .
            "\n   " . '<dados-ec><numero>1006993070</numero><chave>' .
            '25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f4</chave></dados-ec>' . "\n   " .
            '<valor>220248</valor>' . "\n   " . '</requisicao-captura>';
    }

    public function testMakeCaptureRequestXmlFormat()
    {
        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'getCcTransId'
            ], [], '', false);

        $payment
            ->expects($this->once())
            ->method('getCcTransId')
            ->will($this->returnValue('19239123000ACD0'));

        $this
            ->subject
            ->expects($this->once())
            ->method('_getRootElementOpeningTag')
            ->will($this->returnValue('<requisicao-captura id="6105d57e92d0c77293491b80ec605289" versao="1.2.1">'));

        $returnXml = $this->_getTestMakeCaptureRequestXmlFormatRequestXml();
        $this
            ->subject
            ->expects($this->once())
            ->method('_makeRequest')
            ->with('https://ecommerce.cielo.com.br/servicos/ecommwsec.do', $returnXml)
            ->will($this->returnValue(utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><xml-teste><teste1>' .
                '<teste2>asdf</teste2></teste1><teste3><teste4>laga laga</teste4></teste3></xml-teste>')));

        $this->subject->setCompanyId(self::PROD_COMPANY_ID);
        $this->subject->setAccessKey(self::PROD_ACCESS_KEY);
        $this->subject->setAvsActive(true);
        $this->subject->setTestMode(false);

        $return = $this->subject->makeCaptureRequest($payment, 2202.48);
        $this->assertInstanceOf('SimpleXMLElement', $return);
    }

    protected function _getTestMakeRefundRequestXmlFormatRequestXml()
    {
        return '<?xml version="1.0" encoding="ISO-8859-1" ?>' . "\n" .
            '<requisicao-cancelamento id="6105d57e92d0c77293491b80ec605289" versao="1.2.1"><tid>19239123000ACD0</tid>' .
            "\n   " . '<dados-ec><numero>1006993070</numero><chave>' .
            '25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f4</chave></dados-ec>' . "\n   " .
            '</requisicao-cancelamento>';
    }

    public function testMakeRefundRequestXmlFormat()
    {
        $payment = $this
            ->getMock('Magento\Sales\Model\Order\Payment', [
                'getCcTransId'
            ], [], '', false);

        $payment
            ->expects($this->once())
            ->method('getCcTransId')
            ->will($this->returnValue('19239123000ACD0'));

        $this
            ->subject
            ->expects($this->once())
            ->method('_getRootElementOpeningTag')
            ->will(
                $this->returnValue('<requisicao-cancelamento id="6105d57e92d0c77293491b80ec605289" versao="1.2.1">')
            );

        $returnXml = $this->_getTestMakeRefundRequestXmlFormatRequestXml();
        $this
            ->subject
            ->expects($this->once())
            ->method('_makeRequest')
            ->with('https://ecommerce.cielo.com.br/servicos/ecommwsec.do', $returnXml)
            ->will($this->returnValue(utf8_decode('<?xml version="1.0" encoding="ISO-8859-1" ?><xml-teste><teste1>' .
                '<teste2>asdf</teste2></teste1><teste3><teste4>laga laga</teste4></teste3></xml-teste>')));

        $this->subject->setCompanyId(self::PROD_COMPANY_ID);
        $this->subject->setAccessKey(self::PROD_ACCESS_KEY);
        $this->subject->setAvsActive(true);
        $this->subject->setTestMode(false);

        $return = $this->subject->makeRefundRequest($payment, 2202.48);
        $this->assertInstanceOf('SimpleXMLElement', $return);
    }

    public function dataProviderTestConvertCcTypeToCieloApi()
    {
        return [
            ['VI', 'visa'],
            ['MC', 'mastercard'],
            ['DN', 'diners'],
            ['DI', 'discover'],
            ['EL', 'elo'],
            ['AE', 'amex'],
            ['JCB', 'jcb'],
            ['AU', 'aura']
        ];
    }

    /**
     * @dataProvider dataProviderTestConvertCcTypeToCieloApi
     */
    public function testConvertCcTypeToCieloApiCheckKnownCardsConversion($input, $expectedOutput)
    {
        $this->assertEquals($this->convertCardTypeMethod->invoke($this->originalSubject, $input), $expectedOutput);
    }

    public function testConvertCcTypeToCieloApiUnknownCardShouldThrowException()
    {
        $this->setExpectedException(LocalizedException::class);
        $this->convertCardTypeMethod->invoke($this->originalSubject, 'thiscarddoesntexist');
    }

    public function testGetRootElementOpeningTagHasCorrectFormat()
    {
        $expectedRootElement = '<requisicao-transacao id="3858f62230ac3c915f300c664312c63f" versao="1.2.1">';
        $this->assertEquals($this->getRootElementMethod->invoke($this->originalSubject, 'transacao', md5('foobar')),
            $expectedRootElement);
    }
}
