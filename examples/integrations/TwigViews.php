<?php

namespace Krag;

class TwigViews implements ViewsInterface
{
    public function __construct(
        protected string $templatePath = 'templates',
        protected array $templateOptions = [
            'cache' => false,
            'autoescape' => 'name',
            'auto_reload' => true,
        ],
    ) {
    }

    protected function templateFile(string $controllerName, string $methodName): string
    {
        $controllerName = str_replace('\\', '_', $controllerName);
        return $this->templatePath.\DIRECTORY_SEPARATOR.$controllerName.\DIRECTORY_SEPARATOR.$methodName.'.html.twig';
    }

    protected function setupTemplateEngine(string $controllerName, string $methodName): object
    {
        $loader = new \Twig\Loader\FilesystemLoader($this->templatePath);
        return new \Twig\Environment($loader, $this->templateOptions);
    }

    protected function fillTemplate(string $controllerName, string $methodName, array $data): string
    {
        $engine = $this->setupTemplateEngine($controllerName, $methodName);
        return $engine->render($this->templateFile($controllerName, $methodName), $data);
    }

    public function render(string $controllerName, string $methodName, array $methodData, array $globalData, RoutingInterface $routing)
    {
        $content = $this->fillTemplate($controllerName, $methodName, array_merge($globalData, $methodData));
        print($content);
    }
}
