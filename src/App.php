<?php

namespace Krag;

use Psr\Http\Message\ServerRequestInterface;

class App implements AppInterface
{
    protected array $controllers = [];

    public function __construct(
        protected InjectionInterface $injection,
        protected RoutingInterface $routing,
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
            array_map(
                function ($method) {
                    return $this->injection->callMethod($method, withValues: $requestData);
                },
                $this->globalFetchers
            )
        );
    }

    protected function methodRegistered($controllerName, $methodName): bool
    {
        return (
            array_key_exists($controllerName, $this->controllers) &&
            in_array($methodName, $this->controllers[$controllerName])
        );
    }

    protected function getMethodData(string $controllerName, string $methodName, ServerRequestInterface $request): mixed
    {
        if (!count($this->controllers)) {
            $this->registerController($controllerName);
        }
        if ($this->methodRegistered($controllerName, $methodName)) {
            $requestData = array_merge($request->getQueryParams(), $request->getParsedBody());
            return $this->injection->callMethod($this->controllers[$controllerName], $methodName, $requestData);
        }
        return [];
    }

    protected function requestIn(ServerRequestInterface $request): array
    {
        $method = $this->routing->methodForRequest($request, $this->controllers);
        $globalData = $this->processGlobalFetchers($request);
        if (is_array($method)) {
            [$controllerName, $methodName] = $method;
            $response = $this->getMethodData($controllerName, $methodName);
        } else {
            $controllerName = static::class;
            $methodName = (is_string($method)) ? $method : 'notFound';
            $response = [];
        }
        return [$response, $controllerName, $methodName, $globalData];
    }

    protected function responseOut(mixed $response, string $controllerName, string $methodName, array $globalData)
    {
        if ($response instanceof Response) {
            $redirectURL = null;
            if ($response->isRedirect) {
                $redirectURL = $this->routing->makeLink($controllerName, $methodName, $request['uri'], $response->data);
            }
            $http->handleResponse($response, $redirectURL);
        }
        if (is_array($response) || ($response instanceof Response && !$response->isRedirect)) {
            $methodData = is_array($response) ? $response : $response->data;
            $this->views->render($controllerName, $methodName, $methodData, $globalData, $this->routing);
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

    public function run(ServerRequestInterface $request)
    {
        [$response, $controllerName, $methodName, $globalData] = $this->requestIn($request);
        $this->responseOut($response, $controllerName, $methodName, $globalData);
    }
}
