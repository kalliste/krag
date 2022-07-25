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

    public static function fromString(string $str): LogLevel
    {
        return match ($str) {
            'trace'     => LogLevel::TRACE,
            'debug'     => LogLevel::DEBUG,
            'info'      => LogLevel::INFO,
            'notice'    => LogLevel::NOTICE,
            'warning'   => LogLevel::WARNING,
            'error'     => LogLevel::ERROR,
            'critical'  => LogLevel::CRITICAL,
            'alert'     => LogLevel::ALERT,
            'emergency' => LogLevel::EMERGENCY,
            default     => LogLevel::DEBUG,
        };
    }

    public function toString(): string
    {
        return match ($this) {
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

    public function toPSR(): string
    {
        return match ($this) {
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
