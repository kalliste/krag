<?php

namespace Krag;

class Log implements LogInterface, \IteratorAggregate
{

    private array $messages = [];

    public function __construct(
        public ?string $module = null,
        public ?Log $leader = null,
        public LogLevel $minLevel = LogLevel::TRACE,
    ) {}

    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->messages);
    }

    public function makeFollower(string $module): Log
    {
        return new static($module, $this, $this->minLevel);
    }

    protected function handleLog(LogLevel $level, string $message, array $data = [], ?string $module = null) : Log
    {
        if ($level->value >= $this->minLevel->value)
        {
            $module = (is_string($module)) ? $module : $this->module;
            if (is_object($this->leader))
            {
                [$this->leader, $level->toString()]($message, $data, $module);
            }
            else
            {
                $this->messages[] = new LogEntry($level, $message, time(), $data, $module);
            }
        }
        return $this;
    }

    public function filter(LogLevel $minLevel = LogLevel::TRACE, ?string $module = null) : array
    {
        $ret = [];
        foreach ($this->messages as $message)
        {
            $matchModule = (!$module || $module == $message->module);
            $matchLevel = ($message->level->value >= $minLevel->value);
            if ($matchModule && $matchLevel)
            {
                $ret[] = $message;
            }
        }
        return $ret;
    }

    public function trace(string $message, array $data = [], ?string $module = null) : Log
    {
        return $this->handleLog(LogLevel::TRACE, $message, $data, $module);
    }

    public function debug(string $message, array $data = [], ?string $module = null) : Log
    {
        return $this->handleLog(LogLevel::DEBUG, $message, $data, $module);
    }

    public function info(string $message, array $data = [], ?string $module = null) : Log
    {
        return $this->handleLog(LogLevel::INFO, $message, $data, $module);
    }

    public function warn(string $message, array $data = [], ?string $module = null) : Log
    {
        return $this->handleLog(LogLevel::WARN, $message, $data, $module);
    }

    public function error(string $message, array $data = [], ?string $module = null) : Log
    {
        return $this->handleLog(LogLevel::ERROR, $message, $data, $module);
    }

    public function fatal(string $message, array $data = [], ?string $module = null) : Log
    {
        return $this->handleLog(LogLevel::FATAL, $message, $data, $module);
    }

}

?>
