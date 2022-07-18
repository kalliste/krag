<?php

namespace Krag;

class App
{

    protected array $controllers = [];
    protected array $methodTree = [];
    protected array $templateGlobalFetchers = [];
    protected array $request = [];

    public function __construct(
        protected string $controllerPath = "controllers",
        protected string $templatePath = "templates",
        protected array $templateOptions = [ 
            'cache' => false,
            'autoescape' => 'name',
            'auto_reload' => true,
        ],
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

    protected function registerController(object $controller, string $name)
    {
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

    protected function processTemplateGlobalFetchers() : array
    {
        $ret = [];
        foreach ($this->templateGlobalFetchers as $name => $method)
        {
            $ret[$name] = call_user_func($method);
        }
        return $ret;
    }

    protected function templateFile(string $controllerName, string $methodName) : string
    {
        return $methodName.".html.twig";
    }

    protected function setupTemplateEngine(string $controllerName, string $methodName) : object
    {
        $loader = new \Twig\Loader\FilesystemLoader($this->templatePath);
        return new \Twig\Environment($loader, $this->templateOptions);
    }

    protected function fillTemplate(string $controllerName, string $methodName, array $data) : string
    {
        $data = array_merge($this->processTemplateGlobalFetchers(), $data);
        $engine = $this->setupTemplateEngine($controllerName, $methodName);
        return $engine->render($this->templateFile($controllerName, $methodName), $data);
    }

    protected function renderResult(string $controllerName, string $methodName, array $data)
    {
        $content = $this->fillTemplate($controllerName, $methodName, $data);
        print($content);
    }

    public function addTemplateGlobalFetcher(string $name, callable $method)
    {
        $this->templateGlobalFetchers[$name] = $method;
    }

    public function run(array $request)
    {
        $this->request = $request;
        $this->preFlight();
        $this->registerControllers();
        [$controller, $controllerName, $methodName, $arguments] = $this->findHandler($request);
        $data = $this->callHandler($controller, $methodName, $arguments, $request);
        $this->renderResult($controllerName, $methodName, $data);
        $this->cleanup();
    }

}

?>
