<?php

namespace Krag;

class App
{

    protected array $controllers = [];
    protected array $methodTree = [];

    public function __construct(
        protected Views $views,
        protected string $controllerPath = "controllers",
        protected array $globalFetchers = [],
        protected bool $registerControllersOnRun = true,
    ) {}

    protected function isHiddenControllerMethod($class, $methodName) : bool {
        return (substr($methodName, 0, 1) == '_');
    }

    protected function mapMethods(object $object) : array
    {
        $class = get_class($object);
        $methods = [];
        foreach (get_class_methods($object) as $methodName)
        {
            if (!$this->isHiddenControllerMethod($class, $methodName))
            {
                $rmethod = new \ReflectionMethod($class, $methodName);
                $methods[$methodName] = [];
                foreach ($rmethod->getParameters() as $param)
                {
                    $methods[$methodName][] = $param->getName();
                }
            }
        }
        return $methods;
    }

    protected function preFlight()
    {
    }

    protected function cleanup()
    {
    }

    protected function index() : array
    {
        return [];
    }

    protected function notFound() : array
    {
        return [];
    }

    public function registerController(object $controller, ?string $name = null)
    {
        if (!$name)
        {
            $name = get_class($controller);
        }
        $this->controllers[$name] = $controller;
        $this->methodTree[$name] = $this->mapMethods($controller);
    }

    /**
     * Default controller loader
     *
     * @param string $path A directory containing files containing controller classes of the same name
     *
     */
    protected function registerControllers()
    {
        foreach (scandir($this->controllerPath) as $fileName)
        {
            $parts = explode('.', $fileName);
            if (end($parts) == 'php')
            {
                require_once($this->controllerPath.\DIRECTORY_SEPARATOR.$fileName);
                $class = reset($parts);
                $this->registerController(new $class(), $class);
            }
        }
    }

    protected function findHandler(array $request) : array
    {
        $action = array_key_exists('action', $request) ? $request['action'] : 'index';
        foreach ($this->methodTree as $controllerName => $methods)
        {
            if (array_key_exists($action, $methods))
            {
                return [$this->controllers[$controllerName], $controllerName, $action, $methods[$action]];
            }
        }
        $action = ($action == 'index') ? 'index' : 'notFound';
        return [$this, 'App', $action, []];
    }

    protected function callHandler(object $controller, string $methodName, array $arguments, array $request) : array
    {
        $pass = [];
        foreach ($arguments as $argument)
        {
            $pass[] = (array_key_exists($argument, $request)) ? $request[$argument] : '';
        }
        return call_user_func_array([$controller, $methodName], $pass);
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

    public function run(array $request)
    {
        $this->preFlight();
        if ($this->registerControllersOnRun)
        {
            $this->registerControllers();
        }
        [$controller, $controllerName, $methodName, $arguments] = $this->findHandler($request);
        $methodData = $this->callHandler($controller, $methodName, $arguments, $request);
        $allData = array_merge($this->processGlobalFetchers($request), $methodData);
        $this->views->render($controllerName, $methodName, $allData);
        $this->cleanup();
    }

}

?>
