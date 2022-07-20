<?php

namespace Krag;

class Views implements ViewsInterface
{

    public function __construct(protected string $templatePath = 'templates') {}

    protected function templateFile(string $controllerName, string $methodName) : string
    {
        return $this->templatePath.\DIRECTORY_SEPARATOR.$controllerName.\DIRECTORY_SEPARATOR.$methodName.'.html.php';
    }

    public function render(string $controllerName, string $methodName, array $data)
    {
        extract($data);
        include($this->templateFile($controllerName, $methodName));
    }

}

?>
