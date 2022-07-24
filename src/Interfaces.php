<?php

namespace Krag;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface RoutingInterface
{
    public function method(): ?callable;
    public function link(callable $target, array $data = []): string;
}

interface AppInterface
{
    public function run(ServerRequestInterface $request, RoutingInterface $routing);
    public function registerController(string|object $controller, ?string $name = null): AppInterface;
    public function setGlobalFetcher(string $name, callable $method): AppInterface;
}

interface DBInterface
{
    public function begin(): bool;
    public function commit(): bool;
    public function rollBack(): bool;
    public function query(string $query): object;
    public function fetchAssoc(object $result): array;
    public function fetchRow(object $result): array;
    public function closeCursor(object $result): bool;
    public function insertId(): int;
    public function affectedRows(object $result): int;
    public function escape(string|array $toEscape): string|array;
    public function tableEscape(string $toEscape): string;
    public function columnEscape(string|array $toEscape, ?string $table = null): string;
    public function aliasEscape(string $toEscape): string;
    public function setBlob(string $query, string $blob): object;
}

interface HTTPInterface
{
    public function handleResponse(Response $response, ?string $redirectURL = null);
}

interface InjectionInterface extends ContainerInterface
{
    public function get(string $id, array $withValues = []);
    public function callMethod(object|string $objectOrMethod, ?string $method = null, array $withValues = []): mixed;
    public function setSingleton(string $class, ?object $obj = null): InjectionInterface;
    public function setClassMapping(string $fromClass, string $toClass): InjectionInterface;
}

interface InjectionAwareInterface
{
    public function setInjection(InjectionInterface $injection): void;
}

interface LogInterface extends \Psr\Log\LoggerInterface
{
    public function trace(\Stringable|string $message, array $data = [], ?string $component = null): void;
    public function debug(\Stringable|string $message, array $data = [], ?string $component = null): void;
    public function info(\Stringable|string $message, array $data = [], ?string $component = null): void;
    public function warning(\Stringable|string $message, array $data = [], ?string $component = null): void;
    public function error(\Stringable|string $message, array $data = [], ?string $component = null): void;
    public function critical(\Stringable|string $message, array $data = [], ?string $component = null): void;
    public function alert(\Stringable|string $message, array $data = [], ?string $component = null): void;
    public function emergency(\Stringable|string $message, array $data = [], ?string $component = null): void;
    public function log(mixed $level, \Stringable|string $message, array $context = [], ?string $component = null): void;
    public function filter(LogLevel $minLevel = LogLevel::TRACE, ?string $component = null): array;
}

interface ModelInterface
{
    public function value(string $column, array $conditions = []): mixed;
    public function list(string $column, array $conditions = []): array;
    public function assoc(int|array $conditions = [], $idColumn = 'id'): array;
    public function records(array $conditions = [], ?array $pagingParams = null): array;
    public function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null): array;
    public function insert(array $records): int;
    public function update(array $conditions, array $newData): int;
    public function delete(array $conditions = []): int;
    public function replace(array $conditions, array $records): int;
}

interface ResultInterface
{
    public function redirect(callable $method, array $data = [], ?int $responseCode = null, $headers = []): ResultInterface;
    public function getResponse(): Response;
}

interface SQLInterface
{
    public function select(string|array $fields = [], ?string $table = null): SQLInterface;
    public function count(?string $field = null, ?string $table = null, ?string $alias = null): SQLInterface;
    public function from(string $table, ?string $alias = null): SQLInterface;
    public function left(): SQLInterface;
    public function right(): SQLInterface;
    public function inner(): SQLInterface;
    public function outer(): SQLInterface;
    public function cross(): SQLInterface;
    public function natural(): SQLInterface;
    public function join(string $table, ?string $alias = null): SQLInterface;
    public function where(array $conditions = [], ?string $table = null, string $operator = ''): SQLInterface;
    public function eq(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function lt(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function lte(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function gt(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function gte(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function group(string|array $groupBy): SQLInterface;
    public function order(string $sort, ?string $maybeDesc = null, ...$more): SQLInterface;
    public function limit(int $per_page, int $page = 1): SQLInterface;
    public function orderLimit(array $pagingParams): SQLInterface;
    public function value(): mixed;
    public function list(): array;
    public function assoc(): array;
    public function assocList(): array;
    public function map(): array;
    public function insert(string $table, array $records): int;
    public function update(string $table, array $conditions, array $newData): int;
    public function delete(string $table, array $conditions = []): int;
    public function replace(string $table, array $conditions, array $records): int;
    public function setBlob(string $table, string $column, string $blob, array $conditions = []): int;
}

interface ViewsInterface
{
    public function render(string $controllerName, string $methodName, array $methodData, array $globalData, RoutingInterface $routing);
}
