<?php

namespace Krag;

use Psr\Http\Message\ServerRequestInterface;

class App implements AppInterface
{
    protected array $controllers = [];

    public function __construct(
        protected InjectionInterface $injection,
        protected ViewsInterface $views,
        protected HTTPInterface $http,
        protected string $controllerPath = 'controllers',
        protected array $globalFetchers = [],
    ) {
    }

    protected function processGlobalFetchers(ServerRequestInterface $request): array
    {
        $requestData = array_merge($request->getQueryParams(), $request->getParsedBody());
        return array_combine(
            array_keys($this->globalFetchers),
            array_map(fn ($method) => $this->injection->call($method, withValues: $requestData), $this->globalFetchers)
        );
    }

    protected function methodRegistered(callable $method): bool
    {
        if (is_array($method)) {
            [$controllerName, $methodName] = $method;
            return (
                array_key_exists($controllerName, $this->controllers) &&
                in_array($methodName, $this->controllers[$controllerName])
            );
        }
        return false;
    }

    protected function getMethodData(callable $method, ServerRequestInterface $request): mixed
    {
        if (!count($this->controllers) && is_array($method)) {
            [$controllerName, $methodName] = $method;
            $this->registerController($controllerName);
        }
        if ($this->methodRegistered($method)) {
            $requestData = array_merge($request->getQueryParams(), $request->getParsedBody());
            return $this->injection->call($method, withValues: $requestData);
        }
        return [];
    }

    protected function requestIn(ServerRequestInterface $request, RoutingInterface $routing): mixed
    {
        $method = $routing->method() ?? fn () => [];
        $response = [];
        if (is_callable($method)) {
            $response = $this->getMethodData($method, $request);
        }
        return [$response, $method];
    }

    protected function responseOut(mixed $response, callable $method, ServerRequestInterface $request, RoutingInterface $routing)
    {
        [$controllerName, $methodName] = (is_array($method)) ? [$method[0], $method[1]] : [static::class, 'notFound'];
        if ($response instanceof Response) {
            $redirectURL = null;
            if ($response->isRedirect) {
                $redirectURL = $routing->link($method, $response->data);
            }
            $this->http->handleResponse($response, $redirectURL);
        }
        if (is_array($response) || ($response instanceof Response && !$response->isRedirect)) {
            $methodData = is_array($response) ? $response : $response->data;
            $globalData = $this->processGlobalFetchers($request);
            $this->views->render($controllerName, $methodName, $methodData, $globalData, $routing);
        }
    }

    public function setGlobalFetcher(string $name, callable $method): App
    {
        $this->globalFetchers[$name] = $method;
        return $this;
    }

    public function registerController(string|object $controller, ?string $name = null): App
    {
        if (is_string($controller)) {
            if (!class_exists($controller)) {
                $fileName = $this->controllerPath.\DIRECTORY_SEPARATOR.$controller.'.php';
                if (file_exists($fileName)) {
                    require_once($fileName);
                }
            }
            $controller = $this->injection->get($controller);
        }
        $name = (is_string($name)) ? $name : get_class($controller);
        $this->controllers[$name] = get_class_methods($controller);
        return $this;
    }

    //FIXME: implement \Psr\Http\Server\RequestHandlerInterface;

    public function run(ServerRequestInterface $request, RoutingInterface $routing)
    {
        [$response, $method] = $this->requestIn($request, $routing);
        $this->responseOut($response, $method, $request, $routing);
    }
}
