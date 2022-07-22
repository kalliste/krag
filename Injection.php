<?php

namespace Krag;

class Injection implements InjectionInterface
{

    public function __construct(private bool $makeFallbackArguments = false) {}

    protected function matchParamToValues(int $position, string $name, array $withValues) : mixed
    {
        if (count($withValues))
        {
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

    protected function makeArgumentsForMethod(\ReflectionMethod $rMethod, array $withValues = []) : array
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

    public function make(string $class, array $withValues = []) : ?object
    {
        if (class_exists($class))
        {
            $rClass = new \ReflectionClass($class);
            $rConstructor = $rClass->getConstructor();
            $passArguments = $this->makeArgumentsForMethod($rConstructor, $withValues);
            return $rClass->newInstanceArgs($passArguments);
        }
        return null;
    }

    public function callMethod(object|string $objectOrMethod, ?string $method = null, array $withValues = []) : mixed
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
