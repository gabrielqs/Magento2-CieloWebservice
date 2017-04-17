<?php

namespace Gabrielqs\Cielo\Model\Webservice;

use \Magento\Payment\Model\InfoInterface;
use \Magento\Payment\Model\Method\Logger;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Phrase;


/**
 * Api Class, makes all communication with Cielo Webservices
 * @package Gabrielqs\Cielo\Model\Webservice
 */
class Api
{
    /**
     * Sending of CVV Indicator - Not Informed
     */
    const CVV_INDICATOR_NOT_INFORMED = 0;

    /**
     * Sending of CVV Indicator - Informed
     */
    const CVV_INDICATOR_INFORMED = 1;

    /**
     * Sending of CVV Indicator - Unreadable
     */
    const CVV_INDICATOR_UNREADABLE = 2;

    /**
     * Sending of CVV Indicator - Non Existant
     */
    const CVV_INDICATOR_NON_EXISTANT = 9;

    /**
     * Brazilian Real Currency code
     */
    const CURRENCY_CODE_BRL = 986;

    /**
     * Product Type - Credit, one time payment
     */
    const PRODUCT_CREDIT_ONE_TIME = 1;

    /**
     * Product Type, Credit, installments
     */
    const PRODUCT_CREDIT_INSTALLMENTS = 2;

    /**
     * Product Type, Debit
     */
    const PRODUCT_DEBIT = 'A';

    /**
     * Authorization Mode, Authenticate Only
     */
    const AUTHORIZATION_MODE_AUTHENTICATE_ONLY = '0';

    /**
     * Authorization Mode, Only if authenticated
     */
    const AUTHORIZATION_MODE_ONLY_IF_AUTHENTICATED = '1';

    /**
     * Authorization Mode, Authorize All
     */
    const AUTHORIZATION_MODE_AUTHORIZE_ALL = '2';

    /**
     * Authorization Mode, Direct authorization. Currently used by the module.
     */
    const AUTHORIZATION_MODE_DIRECT = '3';

    /**
     * Authorization Mode, Recurring
     */
    const AUTHORIZATION_MODE_RECURRING = '4';

    /**
     * Character Encoding used in the communication
     */
    const XML_ENCODING = "ISO-8859-1";

    /**
     * Cielo Api Version
     */
    const API_VERSION = "1.2.1";

    /**
     * Transaction Status - Authorized
     */
    const TRANSACTION_STATUS_AUTHORIZED = 4;

    /**
     * Transaction Status - Captured
     */
    const TRANSACTION_STATUS_CAPTURED = 6;

    /**
     * Transaction Status - Cancelling
     */
    const TRANSACTION_STATUS_CANCELLING = 12;

    /**
     * Transaction Status - Canceled
     */
    const TRANSACTION_STATUS_CANCELED = 9;

    /**
     *
     */
    const URL_API_PRODUCTION = 'https://ecommerce.cielo.com.br/servicos/ecommwsec.do';

    /**
     *
     */
    const URL_API_TEST = 'https://qasecommerce.cielo.com.br/servicos/ecommwsec.do';

    /**
     * Is Avs Active?
     * @var boolean
     */
    protected $_avsActive = false;

    /**
     * Cielo Company Id (E-mail da Conta Empresarial)
     * @var string|null
     */
    protected $_companyId = null;

    /**
     * Cielo Access Key
     * @var string|null
     */
    protected $_accessKey = null;

    /**
     * Is Test Mode Active?
     * @var boolean
     */
    protected $_testMode = false;

    /**
     * Logger
     * @var Logger
     */
    protected $_logger = null;

    /**
     * Api constructor.
     * @param Logger $logger
     */
    public function __construct (
        Logger $logger
    ) {
        $this->_logger = $logger;
    }


    /**
     * Converts internal magento CC type to Cielo API CC Type
     * @param string $ccType
     * @return string
     * @throws LocalizedException
     */
    protected function _convertCcTypeToCieloApi($ccType)
    {
        $return = null;
        $ccType = strtoupper($ccType);
        switch ($ccType) {
            case 'VI':
                $return = 'visa';
                break;
            case 'MC':
                $return = 'mastercard';
                break;
            case 'DN':
                $return = 'diners';
                break;
            case 'DI':
                $return = 'discover';
                break;
            case 'EL':
                $return = 'elo';
                break;
            case 'AE':
                $return = 'amex';
                break;
            case 'JCB':
                $return = 'jcb';
                break;
            case 'AU':
                $return = 'aura';
                break;
            default:
                throw new LocalizedException(new Phrase('Unknown or unsupported CC Type'));
                break;
        }
        return $return;
    }

    /**
     * Creates the xml for the authorization request
     * @param InfoInterface $payment
     * @param float $amount
     * @return string
     */
    protected function _createAuthRequestXml($payment, $amount)
    {
        $orderId = $payment->getOrder()->getIncrementId();
        $order = $payment->getOrder();
        $installments = $payment->getAdditionalInformation('installment_quantity');
        $productTypeId = ($installments == 1) ? self::PRODUCT_CREDIT_ONE_TIME : self:: PRODUCT_CREDIT_INSTALLMENTS;

        # Header
        $xml = $this->_getXMLHeader();

        # Root Element - Beginning
        $xml .= $this->_getRootElementOpeningTag('transacao', md5(date("YmdHisu")));

        # Cielo Company Info
        $xml .= $this->_getCieloCompanyInfo();

        # Dados Portador
        $xml .=
            '<dados-portador>' .
                '<numero>' . preg_replace('/\D/', '', $payment->getCcNumber()) . '</numero>' .
                '<validade>' . $payment->getCcExpYear() . str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT) .
                    '</validade>' .
                '<indicador>' . self::CVV_INDICATOR_INFORMED . '</indicador>' .
                '<codigo-seguranca>' . $payment->getCcCid() . '</codigo-seguranca>' .
                '<nome-portador>' . $payment->getCcOwner() . '</nome-portador>';

        if ($this->_isAvsActive()) {
            $personType = ((strlen(preg_replace('/\D/', '', $order->getCustomerTaxvat())) == 14) ? 'J' : 'F');
            $xml .=
                '<cnpj-cpf-portador>' . preg_replace('/\D/', '', $order->getCustomerTaxvat()) . '</cnpj-cpf-portador>';
            $xml .=
                '<tipo-pessoa>' . $personType . '</tipo-pessoa>';
        }

        $xml .= '</dados-portador>';

        # Dados do Pedido
        $dateTime = $this->_getDateTime();
        $xml .=
            '<dados-pedido>' .
                '<numero>' . $orderId . '</numero>' .
                '<valor>' . $this->_numberFormat($amount) . '</valor>' .
                '<moeda>' . self::CURRENCY_CODE_BRL . '</moeda>' .
                '<data-hora>' . $dateTime . '</data-hora>' .
            '</dados-pedido>';

        # Forma de Pagamento
        $xml .=
            '<forma-pagamento>' .
                '<bandeira>' . $this->_convertCcTypeToCieloApi($payment->getCcType()) . '</bandeira>' .
                '<produto>' . $productTypeId . '</produto>' .
                '<parcelas>' . $installments . '</parcelas>' .
            '</forma-pagamento>';

        $xml .= '<url-retorno>null</url-retorno>';
        $xml .= '<autorizar>' . self::AUTHORIZATION_MODE_DIRECT . '</autorizar>';
        $xml .= '<capturar>false</capturar>';

        #if (Mage::getStoreConfigFlag('payment/cielooneclick/active')) {
        #
        #   $xml .= '<gerar-token>true</gerar-token>';
        #}

        if ($this->_isAvsActive()) {
            /** @var \Magento\Sales\Model\Order $order */
            $billingAddress = $order->getBillingAddress();
            $xml .=
                '<avs>' .
                    '<![CDATA[' .
                        '<dados-avs>' .
                            '<endereco>' . $billingAddress->getStreetLine(1) . '</endereco>' .
                            '<numero>' . $billingAddress->getStreetLine(2) . '</numero>' .
                            '<complemento>' . ($billingAddress->getStreetLine(3) ?
                                    $billingAddress->getStreetLine(3) : 'ND') .
                                '</complemento>' .
                            '<bairro>' . $billingAddress->getStreetLine(4) . '</bairro>' .
                            '<cep>' . $billingAddress->getPostcode() . '</cep>' .
                        '</dados-avs>' .
                    ']]>' .
                '</avs>';
        }

        $xml .= $this->_getRootElementClosingTag('transacao');

        return $xml;
    }

    /**
     * Creates the xml for the capture request
     * @param InfoInterface $payment
     * @param float $amount
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _createCaptureRequestXml(InfoInterface $payment, $amount)
    {
        $xml  = $this->_getXMLHeader() . "\n";
        $xml .= $this->_getRootElementOpeningTag('captura', md5(date("YmdHisu")));
        $xml .= '<tid>' . $payment->getCcTransId() . '</tid>' . "\n   ";
        $xml .= $this->_getCieloCompanyInfo() . "\n   ";
        $xml .= '<valor>' . $this->_numberFormat($amount) . '</valor>' . "\n   ";
        $xml .= $this->_getRootElementClosingTag('captura');

        return $xml;
    }

    /**
     * Creates the xml for the refund request
     * @param InfoInterface $payment
     * @param float $amount
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _createRefundRequestXml(InfoInterface $payment, $amount)
    {
        $xml  = $this->_getXMLHeader() . "\n";
        $xml .= $this->_getRootElementOpeningTag('cancelamento', md5(date("YmdHisu")));
        $xml .= '<tid>' . $payment->getCcTransId() . '</tid>' . "\n   ";
        $xml .= $this->_getCieloCompanyInfo() . "\n   ";
        $xml .= $this->_getRootElementClosingTag('cancelamento');

        return $xml;
    }

    /**
     * Access Key Getter
     * @return string|null
     */
    protected function _getAccessKey()
    {
        return $this->_accessKey;
    }

    /**
     * Returns the Company info with Cielo, used by all methods
     * @return string
     */
    protected function _getCieloCompanyInfo()
    {
        $xml =
            '<dados-ec>' .
            '<numero>' . $this->_getCompanyId() . '</numero>' .
            '<chave>' . $this->_getAccessKey() . '</chave>' .
            '</dados-ec>';
        return $xml;
    }

    /**
     * Company Id Getter
     * @return string|null
     */
    protected function _getCompanyId()
    {
        return $this->_companyId;
    }

    /**
     * Returns the date/time string used by auth transactions
     * @return string
     */
    protected function _getDateTime()
    {
        return date('Y-m-d', time()) . 'T' . date('H:i:s', time());
    }

    /**
     * Returns the opening tag for the root element used on Cielo Requests
     * @param string $type These are defined on the Cielo Specs, and varies with the transaction kind
     * @param string $identifier Transaction identifier, usually a date time md5
     * @return string
     */
    protected function _getRootElementOpeningTag($type, $identifier)
    {
        return '<requisicao-' .  $type . ' id="' . $identifier . '" versao="' . self::API_VERSION . '">';
    }

    /**
     * Returns the closing tag for the root element used on Cielo Requests
     * @param string $type
     * @return string
     */
    protected function _getRootElementClosingTag($type)
    {
        return '</requisicao-' .  $type . '>';
    }

    /**
     * Returns the Xml Header used by all transactions
     * @return string
     */
    protected function _getXMLHeader()
    {
        return '<?xml version="1.0" encoding="' . self::XML_ENCODING . '" ?>';
    }

    /**
     * Returns the Cielo WS endpoint URL depending on whether test mode is active or not
     * @return string
     */
    protected function _getWsUrl()
    {
        if ($this->_isTestMode()) {
            return self::URL_API_TEST;
        } else {
            return self::URL_API_PRODUCTION;
        }
    }

    /**
     * Avs Active Getter
     * @return boolean
     */
    protected function _isAvsActive()
    {
        return $this->_avsActive;
    }

    /**
     * Test Mode Getter
     * @return bool
     */
    protected function _isTestMode()
    {
        return $this->_testMode;
    }


    /**
     * Makes an Authorization Request
     * @param InfoInterface $payment
     * @param float $amount
     * @return \SimpleXMLElement
     */
    public function makeAuthRequest(InfoInterface $payment, $amount)
    {
        $xmlRequest = $this->_createAuthRequestXml($payment, $amount);
        $response = $this->_makeRequest($this->_getWsUrl(), $xmlRequest);
        return simplexml_load_string($response);
    }

    /**
     * Makes a Capture Request
     * @param InfoInterface $payment
     * @param float $amount
     * @return \SimpleXMLElement
     */
    public function makeCaptureRequest(InfoInterface $payment, $amount)
    {
        $xmlRequest = $this->_createCaptureRequestXml($payment, $amount);
        $response = $this->_makeRequest($this->_getWsUrl(), $xmlRequest);
        return simplexml_load_string($response);
    }

    /**
     * Makes a Refund Request
     * @param InfoInterface $payment
     * @param float $amount
     * @return \SimpleXMLElement
     */
    public function makeRefundRequest(InfoInterface $payment, $amount)
    {
        $xmlRequest = $this->_createRefundRequestXml($payment, $amount);
        $response = $this->_makeRequest($this->_getWsUrl(), $xmlRequest);
        return simplexml_load_string($response);
    }

    /**
     * Makes an HTTP request for the Cielo Web Server
     *
     * @param string $url
     * @param string $xml
     * @return \SimpleXMLElement
     */
    protected function _makeRequest($url, $xml)
    {
        $curlSession = curl_init();

        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curlSession, CURLOPT_SSLVERSION, 1);
        curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, 40);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlSession, CURLOPT_POST, true);
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, 'mensagem=' . $xml);

        $response = curl_exec($curlSession);
        curl_close($curlSession);
        return $response;
    }

    /**
     * Converts floats to the notation used in the integration
     *
     * @param float $float
     * @return string
     */
    protected function _numberFormat($float)
    {
        return number_format($float, 2, '', '');
    }

    /**
     * Access Key Setter
     * @param string $accessKey
     * @return $this
     */
    public function setAccessKey($accessKey)
    {
        $this->_accessKey = (string) $accessKey;
        return $this;
    }

    /**
     * Avs Active Setter
     * @param boolean $avsActive
     * @return $this
     */
    public function setAvsActive($avsActive)
    {
        $this->_avsActive = $avsActive;
        return $this;
    }

    /**
     * Company Id Setter
     * @param string $companyId
     * @return $this
     */
    public function setCompanyId($companyId)
    {
        $this->_companyId = (string) $companyId;
        return $this;
    }

    /**
     * Test Mode Setter
     * @param bool $testMode
     * @return $this
     */
    public function setTestMode($testMode)
    {
        $this->_testMode = (bool) $testMode;
        return $this;
    }

    /*
     * Creates the xml for the token auth request - Commented until we have the recurring method
     *
     * @param InfoInterface $payment
     * @param InfoInterface $info
     * @param float $amount
     * @return string
     */
    /*
    protected function createTokenAuthRequestXml(Gabrielqsecommerce_Cielooneclick_Model_Card $card, $payment, $info, $amount)
    {
        # Variáveis para construção do XML
        $order = $payment->getOrder();
        $valorTotal = $amount;
        if ($this->isTest()) {
            $codigoPedido = 'test' . $payment->getOrder()->getIncrementId();
        } else {
            $codigoPedido = $payment->getOrder()->getIncrementId();
        }
        $parcelas = str_replace(array('A', 'B'), '', $payment->getAdditionalInformation('cielooneclick_parcelamento'));
        if ($parcelas == 1) {
            $produto = self::PRODUTO_CREDITO_A_VISTA;
        } else {
            $produto = self::PRODUTO_CREDITO_PARCELADO;
        }

        # Header
        $xml = $this->getXMLHeader();

        # Início root element
        $xml .= '<requisicao-transacao id="' . md5(date("YmdHisu")) . '" versao="' . self::API_VERSION . '">';

        # Dados Estabelecimento Comercial
        $xml .= $this->getXmlEstabelecimentoComercial();

        # Dados Portador
        $xml .=
            '<dados-portador>' .
                '<token>'
                    . urlencode($card->getToken()) .
                '</token>' .
            '</dados-portador>';

        # Dados do Pedido
        $xml .=
            '<dados-pedido>' .
                '<numero>'
                    . $codigoPedido .
                '</numero>' .
                '<valor>'
                    . $this->numberFormat($valorTotal) .
                '</valor>' .
                '<moeda>'
                    . self::CURRENCY_CODE_BRL .
                '</moeda>' .
                '<data-hora>'
                    . date('Y-m-d', strtotime($order->getCreatedAt())) . 'T' .
                        date('H:i:s', strtotime($order->getCreatedAt())) .
                '</data-hora>' .
            '</dados-pedido>';

        # Forma de Pagamento
        $xml .=
            '<forma-pagamento>' .
                '<bandeira>'
                    . strtolower($this->_formatCcType($card->getCcType())) .
                '</bandeira>' .
                '<produto>'
                    . $produto .
                '</produto>' .
                '<parcelas>'
                    . $parcelas .
                '</parcelas>' .
            '</forma-pagamento>';

        $xml .= '<url-retorno>null</url-retorno>';
        $xml .= '<autorizar>' . self::AUTORIZAR_RECORRENTE . '</autorizar>';
        $xml .= '<capturar>false</capturar>';

        $xml .= '</requisicao-transacao>';


        #$requestXmlAsArray = array(
        #    'bin' => substr($dadosPortador['numero'], 0, 6),
        #    'gerar-token' => $gerarToken
        #);

        return $xml;
    }
    */

    /*
     * Cria o XML de requisição de criação de token - Commented out, as we still don't have recurring payments
     */
    /*
    protected function createTokenRequestXml(Gabrielqsecommerce_Cielooneclick_Model_Card $card)
    {

        # Header
        $xml = $this->getXMLHeader();

        # Início root element
        $xml .= '<requisicao-token id="' . md5(date("YmdHisu")) . '" versao="' . self::API_VERSION . '">';

        # Dados Estabelecimento Comercial
        $xml .= $this->getXmlEstabelecimentoComercial();

        # Dados Portador
        $xml .=
            '<dados-portador>' .
                '<numero>'
                . preg_replace('/\D/', '', $card->getCielooneclickCcNumber()) .
                '</numero>' .
                '<validade>'
                    . $card->getCielooneclickExpirationYr() .
                        str_pad($card->getCielooneclickExpiration(), 2, '0', STR_PAD_LEFT) .
                '</validade>' .
                '<nome-portador>'
                    . $card->getCielooneclickCcOwner() .
                '</nome-portador>' .
            '</dados-portador>';

        $xml .= '</requisicao-token>';

        return $xml;
    }



    public function makeTokenAuthRequest(Gabrielqsecommerce_Cielooneclick_Model_Card $card, $payment, $info, $amount)
    {
        $this->_payment = $payment;
        $this->_card = $card;
        $xmlAuth = $this->createTokenAuthRequestXml($card, $payment, $info, $amount);
        return $this->makeRequest($this->getAuthRequestUrl(), $xmlAuth);
    }



    public function makeCreateTokenRequest(Gabrielqsecommerce_Cielooneclick_Model_Card $card)
    {
        $this->_card = $card;
        $xmlToken = $this->createTokenRequestXml($card);
        return $this->makeRequest($this->getTokenRequestUrl(), $xmlToken);
    }
    */
}
