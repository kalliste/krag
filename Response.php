<?php

namespace Krag;

class Response
{

    public bool $isRedirect = false;
    public $redirectMethod;

    public function __construct(public array $data = [], public ?int $responseCode = null, public $headers = []) {}

    public function redirect(callable $method, array $data = [], ?int $responseCode = null, $headers = [])
    {
        $this->isRedirect = true;
        $this->redirectMethod = $method;
        $this->data = array_merge($this->data, $data);
        $this->headers = array_merge($this->headers, $headers);
        if ($responseCode)
        {
            $this->responseCode = $responseCode;
        }
    }

}

?>
