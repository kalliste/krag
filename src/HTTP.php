<?php

namespace Krag;

class HTTP implements HTTPInterface
{
    public function __construct()
    {
    }

    public function handleResponse(Response $response, ?string $redirectURL = null): void
    {
        if (is_int($response->responseCode)) {
            http_response_code($response->responseCode);
        }
        if ($response->isRedirect && !is_null($redirectURL)) {
            header('Location: '.$redirectURL);
        }
        foreach ($response->headers as $k => $v) {
            header($k.': '.$v);
        }
    }
}
