<?php

namespace Krag;

class Log implements LogInterface, \IteratorAggregate
{

    private array $messages = [];

    public function __construct(
        public ?string $component = null,
        public LogLevel $minLevel = LogLevel::TRACE,
        public ?LogInterface $leader = null,
        public ?InjectionInterface $injection = null,
    ) {}

    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->messages);
    }

    public function makeFollower(string $component): Log
    {
        if (is_null($this->leader))
        {
            return new static($component, $this, $this->minLevel);
        }
        return $this->injection->make(static::class,
            [
                'component' => $component,
                'minLevel' => $this->minLevel,
                'leader' => $this,
            ]
        );
    }

    public function log(mixed $level, \Stringable|string $message, array $context = [], ?string $component = null) : void
    {
        $level = (is_string($level)) ? LogLevel::fromString($level) : $level;
        if ($level->value >= $this->minLevel->value)
        {
            $message = (is_string($message)) ? $message : strval($message);
            $component = (is_string($component)) ? $component : $this->component;
            if (is_object($this->leader))
            {
                [$this->leader, $level->toString()]($message, $context, $component);
            }
            else
            {
                $this->messages[] = new LogEntry($level, $message, $context, $component, time());
            }
        }
    }

    public function filter(LogLevel $minLevel = LogLevel::TRACE, ?string $component = null) : array
    {
        $ret = [];
        foreach ($this->messages as $message)
        {
            $matchModule = (!$component || $component == $message->component);
            $matchLevel = ($message->level->value >= $minLevel->value);
            if ($matchModule && $matchLevel)
            {
                $ret[] = $message;
            }
        }
        return $ret;
    }

    public function trace(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::TRACE, $message, $data, $component);
    }

    public function debug(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::DEBUG, $message, $data, $component);
    }

    public function info(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::INFO, $message, $data, $component);
    }

    public function notice(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::NOTICE, $message, $data, $component);
    }

    public function warning(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::WARNING, $message, $data, $component);
    }

    public function error(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::ERROR, $message, $data, $component);
    }

    public function critical(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::CRITICAL, $message, $data, $component);
    }

    public function alert(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::ALERT, $message, $data, $component);
    }

    public function emergency(\Stringable|string $message, array $data = [], ?string $component = null) : void
    {
        $this->log(LogLevel::EMERGENCY, $message, $data, $component);
    }

}

?>
