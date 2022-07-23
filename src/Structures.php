<?php

namespace Krag;

enum LogLevel: int
{

    case TRACE = 10;
    case DEBUG = 20;
    case INFO = 30;
    case NOTICE = 40;
    case WARNING = 50;
    case ERROR = 60;
    case CRITICAL = 70;
    case ALERT = 80;
    case EMERGENCY = 90;

    public static function fromString(string $str) : LogLevel
    {
        return match($str)
        {
            'trace'     => LogLevel::TRACE,
            'debug'     => LogLevel::DEBUG,
            'info'      => LogLevel::INFO,
            'notice'    => LogLevel::NOTICE,
            'warning'   => LogLevel::WARNING,
            'error'     => LogLevel::ERROR,
            'critical'  => LogLevel::CRITICAL,
            'alert'     => LogLevel::ALERT,
            'emergency' => LogLevel::EMERGENCY,
        };
    }

    public function toString() : string
    {
        return match($this)
        {
            LogLevel::TRACE     => 'trace',
            LogLevel::DEBUG     => 'debug',
            LogLevel::INFO      => 'info',
            LogLevel::NOTICE    => 'notice',
            LogLevel::WARNING   => 'warning',
            LogLevel::ERROR     => 'error',
            LogLevel::CRITICAL  => 'critical',
            LogLevel::ALERT     => 'alert',
            LogLevel::EMERGENCY => 'emergency',
        };
    }

    public function toPSR() : string
    {
        return match($this)
        {
            LogLevel::TRACE     => \Psr\Log\LogLevel::DEBUG,
            LogLevel::DEBUG     => \Psr\Log\LogLevel::DEBUG,
            LogLevel::INFO      => \Psr\Log\LogLevel::INFO,
            LogLevel::NOTICE    => \Psr\Log\LogLevel::NOTICE,
            LogLevel::WARNING   => \Psr\Log\LogLevel::WARNING,
            LogLevel::ERROR     => \Psr\Log\LogLevel::ERROR,
            LogLevel::CRITICAL  => \Psr\Log\LogLevel::CRITICAL,
            LogLevel::ALERT     => \Psr\Log\LogLevel::ALERT,
            LogLevel::EMERGENCY => \Psr\Log\LogLevel::EMERGENCY,
        };
    }

}

class LogEntry
{

    public int $time;

    public function __construct(
        public LogLevel $level,
        public string $message,
        public array $context = [],
        public ?string $component = null,
        ?int $time = null,
    )
    {
        $this->time = (is_null($time)) ? time() : $time;
    }

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
