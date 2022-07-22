<?php

namespace Krag;

class App implements AppInterface
{

    protected array $controllers = [];

    public function __construct(
        protected InjectionInterface $injection,
        protected RoutingInterface $routing,
        protected ViewsInterface $views,
        protected string $controllerPath = 'controllers',
        protected array $globalFetchers = [],
    ) {}

    protected function processGlobalFetchers(array $request) : array
    {
        return array_combine(
            array_keys($this->globalFetchers),
            function ($method)
            {
                return $this->injection->callMethod($method, $request, [], $this);
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
            return $this->injection->callMethod($this->controllers[$controllerName], $methodName, $request, $this);
        }
        return [];
    }

    public function setGlobalFetcher(string $name, callable $method)
    {
        $this->globalFetchers[$name] = $method;
    }

    public function registerController(string|object $controller, ?string $name = null) {
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
            $controller = $this->injection->make($controller, [], $this);
        }
        $name = (is_string($name)) ? $name : get_class($controller);
        $this->controllers[$name] = get_class_methods($controller);
    }

    public function run(?Request $request = null)
    {
        $request = $request ?? new Request($_REQUEST, $_SERVER['URI'], $SERVER['SERVER_NAME'], $_GET, $_POST, $_COOKIE);
        $method = $this->routing->methodForRequest($request, $this->controllers);
        $globalData = $this->processGlobalFetchers($request->request);
        $controllerName = static::class;
        $methodName = (is_string($method)) ? $method : 'notFound';
        $methodData = [];
        if (is_array($method))
        {
            [$controllerName, $methodName] = $method;
            $methodData = $this->getMethodData($controllerName, $methodName);
        }
        $this->views->render($controllerName, $methodName, $methodData, $globalData, $this->routing);
    }

}

?>
