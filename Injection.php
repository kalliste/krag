<?php

namespace Krag;

class Injection implements InjectionInterface
{

    public function __construct(
        protected array $singletons = [],
        protected array $classMappings = [],
        protected bool $makeFallbackArguments = false,
        protected ?InjectionInterface $leader = null,
    ) {
        if (count($singletons) && array_is_list($singletons))
        {
            $this->singletons = array_fill_keys($singletons, null);
        }
        $this->setClassMapping('App', 'Krag\App', 'Krag', true);
        $this->setClassMapping('DB', 'Krag\DB', 'Krag', true);
        $this->setClassMapping('HTTP', 'Krag\HTTP', 'Krag', true);
        $this->setClassMapping('Injection', 'Krag\Injection', 'Krag', true);
        $this->setClassMapping('Log', 'Krag\Log', 'Krag', true);
        $this->setClassMapping('Result', 'Krag\Result', 'Krag', true);
        $this->setClassMapping('Routing', 'Krag\Routing', 'Krag', true);
        $this->setClassMapping('SQL', 'Krag\SQL', 'Krag', true);
        $this->setClassMapping('Views', 'Krag\Views', 'Krag', true);
    }

    protected function matchParamToValues(int $position, string $name, array|object $withValues) : mixed
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

    protected function makeArgumentForParameter(\ReflectionParameter $rParam) : mixed
    {
        $type = strval($rParam->getType());
        $arg = ($this instanceof $type) ? $this : null;
        $arg = $arg ?? $this->matchParamToValues($i, $rParam->getName(), $withValues);
        $arg = $arg ?? $this->make($type);
        $arg = $arg ?? ($rParam->isOptional()) ? $rParam->getDefaultValue() : null;
        if (!$rParam->isOptional())
        {
            $arg = $arg ?? $this->makeArgumentFallback($rMethod, $rParam);
        }
        return $arg;
    }

    protected function makeArgumentsForMethod(\ReflectionMethod $rMethod, array|object $withValues = []) : array
    {
        $passArguments = [];
        $i = 0;
        foreach ($rMethod->getParameters() as $rParam)
        {
            $arg = $this->makeArgumentForParameter($rParam);
            if (!is_null($arg) || !$rParam->isOptional())
            {
                $passArguments[$rParam->getName()] = $arg;
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

    public function setClassMapping(string $fromClass, string $toClass, ?string $andNamespace = null, string|bool $andInterface = false) : InjectionInterface
    {
        $this->classMappings[$fromClass] = $toClass;
        $andInterface = (is_bool($andInterface)) ? 'Interface' : $andInterface;
        if (is_null($andNamespace))
        {
            if ($andInterface)
            {
                $this->classMappings[$fromClass.'Interface'] = $toClass;
            }
        }
        else
        {
            $namespace = rtrim($andNamespace, '\\').'\\';
            $this->classMappings[$namespace.$fromClass] = $toClass;
            if ($andInterface)
            {
                $this->classMappings[$namespace.$fromClass.'Interface'] = $toClass;
            }
        }
        return $ret;
    }

    public function make(string $class, array|object $withValues = []) : ?object
    {
        if ($this->leader)
        {
            return $this->leader->make($class, $withValues);
        }
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

    public function callMethod(object|string $objectOrMethod, ?string $method = null, array|object $withValues = []) : mixed
    {
        if ($this->leader)
        {
            return $this->leader->callMethod($objectOrMethod, $method, $withValues);
        }
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
