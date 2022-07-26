<?php

namespace Krag;

use Psr\Http\Message\{StreamFactoryInterface, ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

class App implements AppInterface
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $controllers = [];

    /**
     * @param array<string, callable> $globalFetchers
     */
    public function __construct(
        protected InjectionInterface $injection,
        protected ViewsInterface $views,
        protected HTTPInterface $http,
        protected ResponseFactoryInterface $responseFactory,
        protected StreamFactoryInterface $streamFactory,
        protected RoutingInterface $routing,
        protected LoggerInterface $log,
        protected string $controllerPath = 'controllers',
        protected array $globalFetchers = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
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
        $methodData = [];
        if (is_callable($method)) {
            $methodData = $this->getMethodData($method, $request);
        }
        return [$methodData, $method];
    }

    protected function startingResponse(): ResponseInterface
    {
        $body = $this->streamFactory->createStream();
        return $this->responseFactory->createResponse()->withBody($body);
    }

    protected function responseOut(mixed $methodReturned, callable $method, ServerRequestInterface $request, RoutingInterface $routing): ResponseInterface
    {
        $response = $this->startingResponse();
        if ($methodReturned instanceof ResultInterface) {
            $response = $methodReturned->applyHeadersToResponse($response, $routing);
            if ($methodReturned->isRedirect()) {
                return $response;
            }
            $methodData = $methodReturned->getData();
        } else {
            $methodData = $methodReturned;
        }
        [$controllerName, $methodName] = (is_array($method)) ? [$method[0], $method[1]] : [static::class, 'notFound'];
        $globalData = $this->processGlobalFetchers($request);
        return $this->views->render($controllerName, $methodName, $methodData, $globalData, $this->routing, $response);
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        [$methodReturned, $method] = $this->requestIn($request, $this->routing);
        return $this->responseOut($methodReturned, $method, $request, $this->routing);
    }

    public function run(ServerRequestInterface $request): void
    {
        $response = $this->handle($request);
        $this->http->sendHeaders($response);
        $this->http->printBody($response);
    }
}
