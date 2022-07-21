<?php

namespace Krag;

class Views implements ViewsInterface
{

    public function __construct(protected string $templatePath = 'templates') {}

    protected function templateFile(string $controllerName, string $methodName) : string
    {
        return $this->templatePath.\DIRECTORY_SEPARATOR.$controllerName.\DIRECTORY_SEPARATOR.$methodName.'.html.php';
    }

    public function render(string $controllerName, string $methodName, array $methodData, array $globalData, RoutingInterface $routing)
    {
        extract(array_merge($globalData, $methodData));
        include($this->templateFile($controllerName, $methodName));
    }

}

?>
