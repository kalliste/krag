<?php

namespace Krag;

use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
class Routing implements RoutingInterface
{
    public function __construct(private readonly ServerRequestInterface $request)
    {
    }

    public function method(): callable|string|null
    {
        $uri = $this->request->getServerParams()['REQUEST_URI'] ?? '';
        $path = parse_url($uri)['path'];
        $urlParts = explode('/', $path);
        if ($path == '/') {
            return 'index';
        }
        if (count($urlParts) >= 3) {
            $controllerName = $urlParts[1];
            $methodName = $urlParts[2];
            return [$controllerName, $methodName];
        }
        return null;
    }

    /**
     * @param array<mixed, mixed> $data
     */
    public function link(callable $target, array $data = []): string
    {
        $fromCurrent = $this->request->getServerParams()['REQUEST_URI'] ?? '';
        $source = explode('/', trim($fromCurrent, '/'));
        while (count($source) && is_array($target) && count($target) && $source[0] == $target[0]) {
            $source = array_slice($source, 1);
            $target = array_slice($target, 1);
        }
        $ret = (count($source)) ? str_repeat('../', count($source)) : '';
        $ret .= implode('/', $target).'/';
        $ret .= (count($data)) ? '?'.http_build_query($data) : '';
        return $ret;
    }
}
