<?php

namespace Krag;

class Views implements ViewsInterface
{
    public function __construct(protected string $templatePath = 'templates')
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
    public function render(string $controllerName, string $methodName, array $methodData, array $globalData, RoutingInterface $routing): void
    {
        extract($globalData);
        extract($methodData);
        include($this->templateFile($controllerName, $methodName));
    }
}
