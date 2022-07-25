<?php

namespace Krag;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

class Result implements ResultInterface
{
    private bool $isRedirect = false;
    private mixed $redirectMethod;

    /**
     * @param array<mixed, mixed> $data
     * @param array<string, string> $headers
     */
    public function __construct(private array $data = [], private ?int $responseCode = null, private array $headers = [])
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

    public function isRedirect(): bool
    {
        return $this->isRedirect;
    }

    public function applyHeadersToResponse(ResponseInterface $response, RoutingInterface $routing): ResponseInterface
    {
        $response = $response->withStatus($this->responseCode);
        if ($this->isRedirect()) {
            $response = $response->withHeader('Location', $routing->link($this->redirectMethod, $this->data));
        }
        foreach ($this->headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        return $response;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function withData(array $data): Result
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): Result
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function withResponseCode(int $responseCode): Result
    {
        $this->responseCode = $responseCode;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, string> $headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }
}
