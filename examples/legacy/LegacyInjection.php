<?php

namespace Krag;

class LegacyInjection extends Injection
{

    protected function makeArgumentFallback(\ReflectionMethod $rMethod, \ReflectionParameter $rParam) : mixed
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

}

?>
