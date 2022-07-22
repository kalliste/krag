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

    public function setGlobalFetcher(string $name, callable $method)
    {
        $this->globalFetchers[$name] = $method;
    }

    public function registerController(string|object $controller, ?string $name = null) {
        if (is_string($controller))
        {
            if (!class_exists($controller))
            {
                require_once($this->controllerPath.\DIRECTORY_SEPARATOR.$controller.'.php');
            }
            $controller = new $controller();
        }
        $name = (is_string($name)) ? $name : get_class($controller);
        $this->controllers[$name] = get_class_methods($controller);
    }

    public function run(?Request $request = null)
    {
        $request = $request ?? new Request($_REQUEST, $_SERVER['URI'], $SERVER['SERVER_NAME'], $_GET, $_POST, $_COOKIE);
        $method = $this->routing->methodForRequest($request, $this->controllers);
        $controllerName = static::class;
        $methodName = 'notFound';
        $methodData = [];
        if (is_string($method))
        {
            $methodName = $method;
        }
        if (is_array($method))
        {
            [$controllerName, $methodName] = $method;
            if (!count($this->controllers))
            {
                $this->registerController($controllerName);
            }
            if ($this->methodRegistered($controllerName, $methodName))
            {
                $methodData = $this->injection->callMethod($controllerName, $methodName, $request, $this);
            }
        }
        $globalData = $this->processGlobalFetchers($request->request);
        $this->views->render($controllerName, $methodName, $methodData, $globalData, $this->routing);
    }

}

?>
