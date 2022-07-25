<?php

namespace Krag;

class LegacyInjection extends Injection
{
    /**
     * @param array<int|string, mixed> $withValues
     */
    protected function makeArgumentFallback(
        \ReflectionParameter $rParam,
        int $position,
        array $withValues,
        bool $preferProvided = false,
    ): mixed {
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
