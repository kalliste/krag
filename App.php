<?php

namespace Krag;

class App implements AppInterface
{

    protected array $controllers = [];
    protected array $methodTree = [];

    public function __construct(
        protected Views $views,
        protected string $controllerPath = 'controllers',
        protected array $globalFetchers = [],
    ) {}

    protected function preFlight()
    {
    }

    protected function cleanup()
    {
    }

    protected function mapMethods(object $object) : array
    {
        $class = get_class($object);
        $methods = [];
        foreach (get_class_methods($object) as $methodName)
        {
            $rmethod = new \ReflectionMethod($class, $methodName);
            $methods[$methodName] = [];
            foreach ($rmethod->getParameters() as $param)
            {
                $methods[$methodName][$param->getName()] = $param;
            }
        }
        return $methods;
    }

    protected function findHandler() : array
    {
        $action = $request->request['action'] ?? 'index';
        foreach ($this->methodTree as $controllerName => $methods)
        {
            if (array_key_exists($action, $methods))
            {
                $handler = [$this->controllers[$controllerName], $action];
                return [$controllerName, $action, $handler, $methods[$action]];
            }
        }
        return [function() { return []; }, ($action == 'index') ? 'index' : 'notFound', []];
    }

    protected function callHandler(callable $handler, array $arguments, ?RequestInfo $request) : mixed
    {
        $pass = array_map(function($argument) { return $request->request[$argumentName] ?? null; }, $arguments);
        return call_user_func_array($handler, $pass);
    }

    protected function processGlobalFetchers($request) : array
    {
        $ret = [];
        foreach ($this->globalFetchers as $name => $method)
        {
            $ret[$name] = call_user_func_array($method, [$request]);
        }
        return $ret;
    }

    public function addGlobalFetcher(string $name, callable $method)
    {
        $this->globalFetchers[$name] = $method;
    }

    public function registerController(string|object $controller, ?string $name = null) { if (is_string($controller))
        {
            if (!class_exists($controller))
            {
                require_once($this->controllerPath.\DIRECTORY_SEPARATOR.$controller.'.php');
            }
            $controller = new $controller();
        }
        $name = (is_string($name)) ? $name : get_class($controller);
        $this->controllers[$name] = $controller;
        $this->methodTree[$name] = $this->mapMethods($controller);
    }

    public function run(?Request $request = null)
    {
        $request = $request ?? new Request($_REQUEST, $_SERVER['URI'], $SERVER['SERVER_NAME'], $_GET, $_POST, $_COOKIES);
        $this->preFlight();
        [$controllerName, $methodName, $handler, $arguments] = $this->findHandler($request);
        $methodData = $this->callHandler($handler, $arguments, $request);
        $allData = array_merge($this->processGlobalFetchers($request), $methodData);
        $this->views->render($controllerName, $methodName, $allData);
        $this->cleanup();
    }

}

?>
