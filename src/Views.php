<?php

namespace Krag;

use Psr\Http\Message\ResponseInterface;

class Views implements ViewsInterface
{
    private ResponseInterface $response;

    public function __construct(private string $templatePath = 'templates')
    {
    }

    protected function templateFile(string $controllerName, string $methodName): string
    {
        $controllerName = str_replace('\\', '_', $controllerName);
        return $this->templatePath.\DIRECTORY_SEPARATOR.$controllerName.\DIRECTORY_SEPARATOR.$methodName.'.html.php';
    }

    /**
     * @param array<string, mixed> $methodData
     * @param array<string, mixed> $globalData
     */
    public function render(string $controllerName, string $methodName, array $methodData, array $globalData, RoutingInterface $routing, ResponseInterface $response): ResponseInterface
    {
        $this->response = $response;
        extract($globalData);
        extract($methodData);
        ob_start();
        include($this->templateFile($controllerName, $methodName));
        $this->response->getBody()->write(ob_get_clean());
        return $response;
    }
}
