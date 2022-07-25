<?php

namespace Krag;

class Result implements ResultInterface
{
    private bool $isRedirect = false;
    private mixed $redirectMethod;

    /**
     * @param array<mixed, mixed> $data
     * @param array<string, string> $headers
     */
    public function __construct(private array $data = [], private ?int $responseCode = null, private $headers = [])
    {
    }

    /**
     * @param array<mixed, mixed> $data
     * @param array<string, string> $headers
     */
    public function redirect(callable $method, array $data = [], ?int $responseCode = null, $headers = []): Result
    {
        $this->isRedirect = true;
        $this->redirectMethod = $method;
        $this->data = array_merge($this->data, $data);
        $this->headers = array_merge($this->headers, $headers);
        if ($responseCode) {
            $this->responseCode = $responseCode;
        }
        return $this;
    }

    public function getResponse(): Response
    {
        return new Response($this->data, $this->responseCode, $this->headers, $this->isRedirect, $this->redirectMethod);
    }
}
