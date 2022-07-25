<?php

namespace Krag;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class Views implements ViewsInterface
{
    public function __construct(private RoutingInterface $routing, protected StreamInterface $stream, private string $templatePath = 'templates')
    {
    }

    protected function templateFile(string $controllerName, string $methodName): string
    {
        $controllerName = str_replace('\\', '_', $controllerName);
        return $this->templatePath.\DIRECTORY_SEPARATOR.$controllerName.\DIRECTORY_SEPARATOR.$methodName.'.html.php';
    }

    protected function writeToStream(string $buf): string
    {
        $this->stream->write($buf);
        return '';
    }

    /**
     * @param array<string, mixed> $methodData
     * @param array<string, mixed> $globalData
     */
    public function render(string $controllerName, string $methodName, array $methodData, array $globalData, ResponseInterface $response): ResponseInterface
    {
        $routing = $this->routing;
        extract($globalData);
        extract($methodData);
        ob_start($this->writeToStream(...));
        include($this->templateFile($controllerName, $methodName));
        $this->writeToStream(ob_get_clean());
        return $response->withBody($this->stream);
    }
}
