<?php

namespace PaymentsV4;

use PaymentsV4\Services\ResponseParser;
use PayU\Alu\Exceptions\ClientException;
use PayU\Alu\Exceptions\ConnectionException;
use PayU\Alu\Request;
use PaymentsV4\Services\HashService;
use PaymentsV4\Services\HTTPClient;
use PaymentsV4\Services\RequestBuilder;
use PaymentsV4\Services\ResponseBuilder;
use PayU\PaymentsApi\Interfaces\AuthorizationInterface;

class PaymentsV4 implements AuthorizationInterface
{
    const PAYMENTS_API_AUTHORIZE_PATH = '/api/v4/payments/authorize';
    const API_VERSION_V4 = 'v4';

    /**
     * @var array
     * todo set ro to original value
     */
    private $aluUrlHostname = array(
        //'ro' => 'https://secure.payu.ro',
        'ro' => 'http://ro.payu.local',
        'ru' => 'https://secure.payu.ru',
        'ua' => 'https://secure.payu.ua',
        'hu' => 'https://secure.payu.hu',
        'tr' => 'https://secure.payu.com.tr',
    );

    /**
     * @var string
     */
    private $customUrl = null;

    /**
     * @var HashService
     */
    private $hashService;

    /**
     * @var HTTPClient
     */
    private $httpClient;

    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * @var ResponseParser
     */
    private $responseParser;

    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * PaymentsApiGateway constructor.
     *
     * @throws ClientException
     */
    public function __construct()
    {
        $this->httpClient = new HTTPClient();
        $this->hashService = new HashService();
        $this->requestBuilder = new RequestBuilder();
        $this->responseParser = new ResponseParser();
        $this->responseBuilder = new ResponseBuilder();
    }

    /**
     * @param string $platform
     * @return string
     * @throws ClientException
     */
    private function getPaymentsUrl($platform)
    {
        if (!empty($this->customUrl)) {
            return $this->customUrl;
        }

        if (!isset($this->aluUrlHostname[$platform])) {
            throw new ClientException('Invalid platform');
        }

        return $this->aluUrlHostname[$platform] . self::PAYMENTS_API_AUTHORIZE_PATH;
    }

    /**
     * @inheritDoc
     */
    public function authorize(Request $request)
    {
        $jsonRequest = $this->requestBuilder->buildAuthorizationRequest($request);

        $apiSignature = $this->hashService->generateSignature(
            $request->getMerchantConfig(),
            $request->getOrder()->getOrderDate(),
            $jsonRequest
        );

        $headers = $this->httpClient->buildRequestHeaders(
            $request->getMerchantConfig(),
            $request->getOrder()->getOrderDate(),
            $apiSignature
        );

        try {
            $responseJson = $this->httpClient->post(
                $this->getPaymentsUrl($request->getMerchantConfig()->getPlatform()),
                $jsonRequest,
                $headers
            );
        } catch (ClientException $e) {
            echo($e->getMessage() . ' ' . $e->getCode());
        } catch (ConnectionException $e) {
            echo($e->getMessage() . ' ' . $e->getCode());
        }

        $authorizationResponse = $this->responseParser->parseJsonResponse($responseJson);

        return $this->responseBuilder->buildResponse($authorizationResponse);
    }
}