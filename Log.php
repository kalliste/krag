<?php

namespace Krag;

enum LogLevel: int
{

    case TRACE = 10;
    case DEBUG = 20;
    case INFO = 30;
    case WARN = 40;
    case ERROR = 50;
    case FATAL = 60;

    public function string()
    {
        return match($this)
        {
            LogLevel::TRACE => 'trace',
            LogLevel::DEBUG => 'debug',
            LogLevel::INFO => 'info',
            LogLevel::ERROR => 'error',
            LogLevel::WARN => 'warn',
            LogLevel::FATAL => 'fatal',
        };
    }

}

class LogEntry
{

    public function __construct(
        public LogLevel $level,
        public string $message,
        public int $time,
        public array $data = [],
        public ?string $module = null,
    ) {}

}

class Log
{

    public array $messages = [];

    public function __construct(
        public ?string $module = null,
        public ?Log $leader = null,
        public LogLevel $minLevel = LogLevel::TRACE,
    ) {}

    public function makeFollower(string $module): Log
    {
        return new static($module, $this, $this->minLevel);
    }

    protected function handleLog(LogLevel $level, string $message, array $data = [], ?string $module = null)
    {
        if ($level->value >= $this->minLevel->value)
        {
            if (!$module)
            {
                $module = $this->module;
            }
            if (is_object($this->leader))
            {
                [$this->leader, $level->string()]($message, $data, $module);
            }
            else
            {
                $this->messages[] = new LogEntry($level, $message, time(), $data, $module);
            }
        }
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

    public function trace(string $message, array $data = [], ?string $module = null)
    {
        $this->handleLog(LogLevel::TRACE, $message, $data, $module);
    }

    public function debug(string $message, array $data = [], ?string $module = null)
    {
        $this->handleLog(LogLevel::DEBUG, $message, $data, $module);
    }

    public function info(string $message, array $data = [], ?string $module = null)
    {
        $this->handleLog(LogLevel::INFO, $message, $data, $module);
    }

    public function warn(string $message, array $data = [], ?string $module = null)
    {
        $this->handleLog(LogLevel::WARN, $message, $data, $module);
    }

    public function error(string $message, array $data = [], ?string $module = null)
    {
        $this->handleLog(LogLevel::ERROR, $message, $data, $module);
    }

    public function fatal(string $message, array $data = [], ?string $module = null)
    {
        $this->handleLog(LogLevel::FATAL, $message, $data, $module);
    }

}

?>
