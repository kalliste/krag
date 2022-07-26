<?php

namespace Krag;

use Psr\Http\Message\ResponseInterface;

class HTTP implements HTTPInterface
{
    public function __construct()
    {
    }

    public function sendHeaders(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
    }

    public function printBody(ResponseInterface $response): void
    {
        print(strval($response->getBody()));
    }
}
