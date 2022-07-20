<?php

namespace Krag;

class Response implements ResponseInterface
{

    private bool $isRedirect = false;
    private $redirectMethod;

    public function __construct(private array $data = [], private ?int $responseCode = null, private $headers = []) {}

    public function redirect(callable $method, array $data = [], ?int $responseCode = null, $headers = []) : Response
    {
        $this->isRedirect = true;
        $this->redirectMethod = $method;
        $this->data = array_merge($this->data, $data);
        $this->headers = array_merge($this->headers, $headers);
        if ($responseCode)
        {
            $this->responseCode = $responseCode;
        }
        return $this;
    }

    public function getResponseInfo() : ResponseInfo
    {
        return new ResponseInfo($this->data, $this->getResponseCode, $this->headers, $this->isRedirect, $this->redirectMethod);
    }

}

?>
