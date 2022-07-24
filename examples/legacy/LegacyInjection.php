<?php

namespace Krag;

class LegacyInjection extends Injection
{
    protected function makeArgumentFallback(\ReflectionParameter $rParam): mixed
    {
        return match (strval($rParam->getType())) {
            '' => '',
            'string' => '',
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
            default => ''
        };
    }
}
