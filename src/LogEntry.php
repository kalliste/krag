<?php

namespace Krag;

class LogEntry
{
    public int $time;

    /**
     * @param array<int|string, mixed> $context
     */
    public function __construct(
        public LogLevel $level,
        public string $message,
        public array $context = [],
        public ?string $component = null,
        ?int $time = null,
    ) {
        $this->time = (is_null($time)) ? time() : $time;
    }
}
