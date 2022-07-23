<?php

namespace Krag;

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
    ) {}

    protected function processGlobalFetchers(array $request) : array
    {
        return array_combine(
            array_keys($this->globalFetchers),
            function ($method)
            {
                return $this->injection->callMethod($method, withValues: $request);
            }
        );
    }

    protected function methodRegistered($controllerName, $methodName) : bool
    {
        return (
            array_key_exists($controllerName, $this->controllers) &&
            in_array($methodName, $this->controllers[$controllerName])
        );
    }

    protected function getMethodData(string $controllerName, string $methodName) : mixed
    {
        if (!count($this->controllers))
        {
            $this->registerController($controllerName);
        }
        if ($this->methodRegistered($controllerName, $methodName))
        {
            return $this->injection->callMethod($this->controllers[$controllerName], $methodName, $request);
        }
        return [];
    }

    protected function defaultRequest() : Request
    {
        return $this->injection->get('Request',
            [
                'request' => $_REQUEST,
                'uri' => $_SERVER['uri'],
                'serverName' => $_SERVER['SERVER_NAME'],
                'get' => $_GET,
                'post' => $_POST,
                'cookies' => $_COOKIE,
            ]
        );
    }

    protected function requestIn(Request $request) : array
    {
        $method = $this->routing->methodForRequest($request, $this->controllers);
        $globalData = $this->processGlobalFetchers($request->request);
        if (is_array($method))
        {
            [$controllerName, $methodName] = $method;
            $response = $this->getMethodData($controllerName, $methodName);
        }
        else
        {
            $controllerName = static::class;
            $methodName = (is_string($method)) ? $method : 'notFound';
            $response = [];
        }
        return [$response, $controllerName, $methodName];
    }

    protected function responseOut(mixed $response, string $controllerName, string $methodName)
    {
        if ($response instanceof Response)
        {
            $redirectURL = null;
            if ($response->isRedirect)
            {
                $redirectURL = $this->routing->makeLink($controllerName, $methodName, $request['uri'], $response->data);
            }
            $http->handleResponse($response, $redirectURL);
        }
        if (is_array($response) || ($response instanceof Response && !$response->isRedirect))
        {
            $methodData = is_array($response) ? $response : $response->data;
            $this->views->render($controllerName, $methodName, $methodData, $globalData, $this->routing);
        }
    }

    public function setGlobalFetcher(string $name, callable $method) : App
    {
        $this->globalFetchers[$name] = $method;
        return $this;
    }

    public function registerController(string|object $controller, ?string $name = null) : App
    {
        if (is_string($controller))
        {
            if (!class_exists($controller))
            {
                $fileName = $this->controllerPath.\DIRECTORY_SEPARATOR.$controller.'.php';
                if (file_exists($fileName))
                {
                    require_once($fileName);
                }
            }
            $controller = $this->injection->get($controller);
        }
        $name = (is_string($name)) ? $name : get_class($controller);
        $this->controllers[$name] = get_class_methods($controller);
        return $this;
    }

    public function run(?Request $request = null)
    {
        $request = (is_null($request)) ? $this->defaultRequest : $request;
        [$response, $controllerName, $methodName] = $this->requestIn($request);
        responseOut($response, $controllerName, $methodName);
    }

}

?>
