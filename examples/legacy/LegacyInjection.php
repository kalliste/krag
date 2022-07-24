<?php

namespace Krag;

class LegacyInjection extends Injection
{
    protected function makeArgumentFallback(\ReflectionParameter $rParam): mixed
    {
        return match ($rParam->getType()) {
            '' => '',
            'string' => '',
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
        };
    }
}
