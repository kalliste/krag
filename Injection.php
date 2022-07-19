<?php

namespace Krag;

class Injection
{

    private function isAssoc(array $arr) : bool
    {
        if ([] === $arr)
        {
            return false;
        }
        return (range(0, count($arr) - 1) !== array_keys($arr));
    }

    private function matchParamToArguments(int $position, string $name, array $arguments) : mixed
    {
        if (count($arguments))
        {
            if ($this->isAssoc($arguments))
            {
                if (array_key_exists($name, $arguments))
                {
                    return $arguments[$name];
                }
            }
            else
            {
                if ($position < count($arguments))
                {
                    return $arguments[$position];
                }
            }
        }
        return null;
    }

    private function makeArgumentsForMethod(\ReflectionMethod $rMethod, array $arguments = []) : array
    {
        $passArguments = [];
        $i = 0;
        foreach ($rMethod->getParameters() as $rParam)
        {
            $name = $rParam->getName();
            $arg = $this->matchParamToArguments($i, $name, $arguments);
            if (is_null($arg))
            {
                $arg = $this->make(strval($param->getType()));
            }
            $passArguments[] = $arg;
            $i++;
        }
        return $passArguments;
    }

    public function make(string $class, array $arguments = []) : ?object
    {
        if (class_exists($class))
        {
            $rClass = new \ReflectionClass($class);
            $rConstructor = $rClass->getConstructor();
            $passArguments = $this->makeArgumentsForMethod($rConstructor, $arguments);
            return $rClass->newInstanceArgs($passArguments);
        }
        return null;
    }

    public function callMethodWithInjection(object|string $objectOrMethod, ?string $method = null, array $arguments = []) : mixed
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
        $arguments = $this->makeArgumentsForMethod($rMethod, $arguments);
        return call_user_func_array($toCall, $arguments);
    }

}

?>
