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
            array_map(fn ($method) => $this->injection->callMethod($method, withValues: $requestData), $this->globalFetchers)
        );
    }

    protected function methodRegistered(callable $method): bool
    {
        [$controllerName, $methodName] = $method;
        return (
            array_key_exists($controllerName, $this->controllers) &&
            in_array($methodName, $this->controllers[$controllerName])
        );
    }

    protected function getMethodData(callable $method, ServerRequestInterface $request): mixed
    {
        if (!count($this->controllers)) {
            [$controllerName, $methodName] = $method;
            $this->registerController($controllerName);
        }
        if ($this->methodRegistered($method)) {
            $requestData = array_merge($request->getQueryParams(), $request->getParsedBody());
            return $this->injection->callMethod($method, withValues: $requestData);
        }
        return [];
    }

    protected function requestIn(ServerRequestInterface $request, RoutingInterface $routing): array
    {
        $method = $routing->method();
        $globalData = $this->processGlobalFetchers($request);
        if (is_array($method)) {
            [$controllerName, $methodName] = $method;
            $response = $this->getMethodData($controllerName, $methodName, $request);
        } else {
            $controllerName = static::class;
            $methodName = (is_string($method)) ? $method : 'notFound';
            $response = [];
        }
        return [$response, $controllerName, $methodName, $globalData];
    }

    protected function responseOut(mixed $response, RoutingInterface $routing, callable $method, array $globalData)
    {
        [$controllerName, $methodName] = $method;
        if ($response instanceof Response) {
            $redirectURL = null;
            if ($response->isRedirect) {
                $redirectURL = $routing->link($method, $response->data);
            }
            $this->http->handleResponse($response, $redirectURL);
        }
        if (is_array($response) || ($response instanceof Response && !$response->isRedirect)) {
            $methodData = is_array($response) ? $response : $response->data;
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
        [$response, $controllerName, $methodName, $globalData] = $this->requestIn($request, $routing);
        $this->responseOut($response, $controllerName, $methodName, $globalData);
    }
}
