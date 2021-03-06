<?php

namespace Krag;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use const DIRECTORY_SEPARATOR;

/**
 *
 */
class Views implements ViewsInterface
{
    public function __construct(private readonly string $templatePath = 'templates', private readonly ?LoggerInterface $log = null)
    {
    }

    protected function templateFile(string $controllerName, string $methodName): string
    {
        $controllerName = str_replace('\\', '_', $controllerName);
        return $this->templatePath. DIRECTORY_SEPARATOR . $controllerName . DIRECTORY_SEPARATOR . $methodName . '.html.php';
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function fillTemplate(string $fileName, array $data): void
    {
        $this->debug("fillTemplate $fileName");
        extract($data);
        require(func_get_arg(0));
    }

    protected function debug(string $message): void
    {
        if (!is_null($this->log)) {
            $this->log->debug($message);
        }
    }

    /**
     * @param string $controllerName
     * @param string $methodName
     * @param array<string, mixed> $methodData
     * @param array<string, mixed> $globalData
     * @param RoutingInterface $routing
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function render(string $controllerName, string $methodName, array $methodData, array $globalData, RoutingInterface $routing, ResponseInterface $response): ResponseInterface
    {
        $fileName = $this->templateFile($controllerName, $methodName);
        $this->debug("render $controllerName $methodName $fileName");
        if (file_exists($fileName)) {
            ob_start();
            $this->fillTemplate($fileName, array_merge(compact('routing'), $globalData, $methodData));
            $response->getBody()->write(ob_get_clean());
        }
        return $response;
    }
}
