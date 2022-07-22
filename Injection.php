<?php

namespace Krag;

class Injection implements InjectionInterface
{

    public function __construct(
        protected array $singletons = [],
        protected array $classMappings = [],
        protected bool $makeFallbackArguments = false,
    ) {
        if (count($singletons) && array_is_list($singletons))
        {
            $this->singletons = array_fill_keys($singletons, null);
        }
        $this->setClassMapping('Krag\AppInterface', 'Krag\App');
        $this->setClassMapping('Krag\DBInterface', 'Krag\DB');
        $this->setClassMapping('Krag\InjectionInterface', 'Krag\Injection');
        $this->setClassMapping('Krag\LogInterface', 'Krag\Log');
        $this->setClassMapping('Krag\ResultInterface', 'Krag\Result');
        $this->setClassMapping('Krag\RoutingInterface', 'Krag\Routing');
        $this->setClassMapping('Krag\SQLInterface', 'Krag\SQL');
        $this->setClassMapping('Krag\ViewsInterface', 'Krag\Views');
    }

    protected function matchParamToValues(int $position, string $name, array|object $withValues) : mixed
    {
        if (count($withValues))
        {
            if (is_object($withValues))
            {
                return $withValues->$name ?? null;
            }
            if (array_is_list($withValues) && $position < count($withValues))
            {
                return $withValues[$position];
            }
            if (array_key_exists($name, $withValues))
            {
                return $withValues[$name];
            }
        }
        return null;
    }

    protected function makeArgumentFallback(\ReflectionMethod $rMethod, \ReflectionParameter $rParam) : mixed
    {
        if ($this->makeFallbackArguments)
        {
            return match ($param->getType())
            {
                '' => '',
                'string' => '',
                'int' => 0,
                'float' => 0.0,
                'bool' => false,
            };
        }
        return null;
    }

    protected function makeArgumentsForMethod(\ReflectionMethod $rMethod, array|object $withValues = []) : array
    {
        $passArguments = [];
        $i = 0;
        foreach ($rMethod->getParameters() as $rParam)
        {
            $name = $rParam->getName();
            $arg = $this->matchParamToValues($i, $name, $withValues);
            $arg = $arg ?? $this->make(strval($param->getType()));
            $arg = $arg ?? ($param->isOptional()) ? $param->getDefaultValue() : null;
            $arg = $arg ?? $this->fallback($rMethod, $rParam);
            if (!($param->isOptional() && is_null($arg)))
            {
                $passArguments[$name] = $arg;
            }
            $i++;
        }
        return $passArguments;
    }

    public function setSingleton(string $class, ?object $obj = null) : InjectionInterface
    {
        $this->singletons[$class] = $obj;
        return $this;
    }

    public function setClassMapping(string $fromClass, string $toClass) : InjectionInterface
    {
        $this->classMappings[$fromClass] = $toClass;
        return $this;
    }

    public function make(string $class, array|object $withValues = [], object|string|null $whosAsking = null) : ?object
    {
        $class = $this->classMappings[$class] ?? $class;
        if (array_key_exists($class, $this->singletons) && !is_null($this->singletons[$class]))
        {
            return $this->singletons[$class];
        }
        if (class_exists($class))
        {
            $rClass = new \ReflectionClass($class);
            $rConstructor = $rClass->getConstructor();
            $passArguments = $this->makeArgumentsForMethod($rConstructor, $withValues);
            $obj = $rClass->newInstanceArgs($passArguments);
            if (array_key_exists($class, $this->singletons))
            {
                $this->singletons[$class] = $obj;
            }
            return $obj;
        }
        return null;
    }

    public function callMethod(object|string $objectOrMethod, ?string $method = null, array|object $withValues = [], object|string|null $whosAsking = null) : mixed
    {
        if (is_null($method))
        {
            $rMethod = new \ReflectionMethod($objectOrMethod);
            $toCall = $objectOrMethod;
        }
        else
        {
            $rMethod = new \ReflectionMethod($objectOrMethod, $method);
            $toCall = [$objectOrMethod, $method];
        }
        $arguments = $this->makeArgumentsForMethod($rMethod, $withValues);
        return call_user_func_array($toCall, $arguments);
    }

}

?>
