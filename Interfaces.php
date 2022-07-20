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

class ResponseInfo
{
    public function __construct(
        public array $data = [],
        public ?int $responseCode = null,
        public $headers = [],
        bool $isRedirect = false,
        mixed $redirectMethod = null,
    ) {}
}

interface AppInterface
{
    public function registerController(object $controller, ?string $name = null);
    public function addGlobalFetcher(string $name, callable $method);
    public function run(array $request);
}

interface DBInterface
{
    public function begin() : bool;
    public function commit() : bool;
    public function rollBack() : bool;
    public function query(string $query) : object;
    public function fetchAssoc(object $result) : array;
    public function fetchRow(object $result) : array;
    public function closeCursor(object $result) : bool;
    public function insertId() : int;
    public function affectedRows($result) : int;
    public function escape(string|array $toEscape) : string|array;
    public function tableEscape(string $toEscape) : string;
    public function columnEscape(string|array $toEscape, ?string $table = null) : string;
    public function setBlob(string $query, string $blob) : object;
}

interface InjectionInterface
{
    public function make(string $class, array $arguments = []) : ?object;
    public function callMethodWithInjection(object|string $objectOrMethod, ?string $method = null, array $arguments = []) : mixed;
}

interface LogInterface
{
    public function makeFollower(string $module): LogInterface;
    public function filter(LogLevel $minLevel = LogLevel::TRACE, ?string $module = null) : array;
    public function trace(string $message, array $data = [], ?string $module = null) : LogInterface;
    public function debug(string $message, array $data = [], ?string $module = null) : LogInterface;
    public function info(string $message, array $data = [], ?string $module = null) : LogInterface;
    public function warn(string $message, array $data = [], ?string $module = null) : LogInterface;
    public function error(string $message, array $data = [], ?string $module = null) : LogInterface;
    public function fatal(string $message, array $data = [], ?string $module = null) : LogInterface;
}

interface ResponseInterface
{
    public function redirect(callable $method, array $data = [], ?int $responseCode = null, $headers = []) : ResponseInterface;
    public function getResponseInfo() : ResponseInfo;
}

interface SQLInterface
{
    public function select(string|array $fields = [], ?string $table = null) : SQLInterface;
    public function selectAliased(string|array $fields = [], ?string $table = null) : SQLInterface;
    public function from(string $table, ?string $alias = null) : SQLInterface;
    public function left() : SQLInterface;
    public function right() : SQLInterface;
    public function inner() : SQLInterface;
    public function outer() : SQLInterface;
    public function cross() : SQLInterface;
    public function natural() : SQLInterface;
    public function join(string $table, ?string $alias = null) : SQLInterface;
    public function where(array $conditions = [], ?string $table = null, $operator = '') : SQLInterface;
    public function eq(string $column, mixed $value, ?string $table = null) : SQLInterface;
    public function lt(string $column, mixed $value, ?string $table = null) : SQLInterface;
    public function lte(string $column, mixed $value, ?string $table = null) : SQLInterface;
    public function gt(string $column, mixed $value, ?string $table = null) : SQLInterface;
    public function gte(string $column, mixed $value, ?string $table = null) : SQLInterface;
    public function group(string|array $groupBy) : SQLInterface;
    public function order(string $sort, ?string $maybeDesc = null, ...$more) : SQLInterface;
    public function limit(int $per_page, int $page = 1) : SQLInterface;
    public function value($query) : mixed;
    public function list(string $query) : array;
    public function assoc(string $query) : array;
    public function assocList(string $query) : array;
    public function map(string $query) : array;
    public function insert(string $table, array $records) : int;
    public function update(string $table, array $conditions, array $newData) : int;
    public function replace(string $table, array $conditions, $records) : int;
    public function setBlob(string $table, string $column, string $blob, array $conditions = []) : int;
}

interface ViewsInterface
{
    public function render(string $controllerName, string $methodName, array $data);
}

?>
