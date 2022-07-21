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

    public function toString()
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

class Request
{
    public function __construct(
        array $request = [],
        string $uri = '',
        string $serverName = '',
        array $get = [],
        array $post = [],
        array $cookies = [],
    ) {}
}

class Response
{
    public function __construct(
        public array $data = [],
        public ?int $responseCode = null,
        public $headers = [],
        bool $isRedirect = false,
        mixed $redirectMethod = null,
    ) {}
}

?>
