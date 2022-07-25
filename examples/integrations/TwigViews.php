<?php

namespace Krag;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class TwigViews implements ViewsInterface
{
    /**
     * @param array<string, mixed> $templateOptions
     */
    public function __construct(
        private RoutingInterface $routing,
        private StreamInterface $stream,
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

    /**
     * @param array<string, mixed> $data
     */
    protected function fillTemplate(string $controllerName, string $methodName, array $data): string
    {
        $engine = $this->setupTemplateEngine($controllerName, $methodName);
        return $engine->render($this->templateFile($controllerName, $methodName), $data);
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
        ob_start($this->writeToStream(...));
        $this->writeToStream($this->fillTemplate($controllerName, $methodName, array_merge(['routing' => $this->routing], $globalData, $methodData)));
        $this->writeToStream(ob_get_clean());
        return $response->withBody($this->stream);
    }
}
