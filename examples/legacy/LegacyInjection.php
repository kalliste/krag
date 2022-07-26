<?php

namespace Krag;

class LegacyInjection extends Injection
{
    /**
     * @param array<int|string, mixed> $withValues
     */

    protected function makeArgumentForParameter(
        \ReflectionParameter $rParam,
        int $position,
        array $withValues,
        bool $preferProvided,
    ): mixed {
        $arg = parent::makeArgumentForParameter($rParam, $position, $withValues, $preferProvided);
        return $arg ?? match (strval($rParam->getType())) {
            '' => '',
            'string' => '',
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
            default => ''
        };
    }
}
