<?php


namespace PayU\PaymentsApi\AluV3\Entities;

use SimpleXMLElement;

final class AuthorizationResponse
{
    /**
     * @var SimpleXMLElement
     */
    private $response;

    /**
     * AuthorizationResponse constructor.
     *
     * @param SimpleXMLElement $response
     */
    public function __construct(SimpleXMLElement $response)
    {
        $this->response = $response;
    }

    /**
     * @return SimpleXMLElement
     */
    public function getResponse()
    {
        return $this->response;
    }
}
