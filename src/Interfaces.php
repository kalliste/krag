<?php

namespace Krag;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

interface RoutingInterface
{
    public function method(): callable|string|null;
    /**
     * @param array<mixed, mixed> $data
     */
    public function link(callable $target, array $data = []): string;
}

interface AppInterface extends RequestHandlerInterface
{
    public function registerController(string|object $controller, ?string $name = null): AppInterface;
    public function setGlobalFetcher(string $name, callable $method): AppInterface;
}

interface DBInterface
{
    public function begin(): bool;
    public function commit(): bool;
    public function rollBack(): bool;
    public function query(string $query): object;
    /**
     * @return array<string, mixed>
     */
    public function fetchAssoc(object $result): array;
    /**
     * @return array<int, mixed>
     */
    public function fetchRow(object $result): array;
    public function closeCursor(object $result): bool;
    public function insertId(): int;
    public function affectedRows(object $result): int;
    /**
     * @param string|array<mixed> $toEscape
     * @return string|array<mixed>
     */
    public function escape(string|array $toEscape): string|array;
    public function tableEscape(string $toEscape): string;
    /**
      * @param string|array<mixed> $toEscape
      */
    public function columnEscape(string|array $toEscape, ?string $table = null): string;
    public function aliasEscape(string $toEscape): string;
    public function setBlob(string $query, string $blob): object;
}

interface HTTPInterface
{
    public function sendHeaders(ResponseInterface $response): void;
    public function printBody(ResponseInterface $response): void;
}

interface InjectionGetHasInterface extends ContainerInterface
{
    /**
     * @param array<int|string, mixed> $withValues
     */
    public function get(string $id, array $withValues = [], bool $preferProvided = true);
}

interface InjectionCallInterface
{
    /**
     * @param array<int|string, mixed> $withValues
     */
    public function call(callable $method, array $withValues = [], bool $preferProvided = false): mixed;
}

interface InjectionMappingInterface
{
    /**
     * @param string|array<int|string, string> $from
     */
    public function setMapping(string|array $from, object|callable|string $to): InjectionInterface;
}

interface InjectionInterface extends InjectionGetHasInterface, InjectionCallInterface, InjectionMappingInterface
{
}

interface ModelInterface
{
    /**
     * @param array<string, mixed> $conditions
     */
    public function value(string $column, array $conditions = []): mixed;
    /**
     * @param array<string, mixed> $conditions
     * @return array<int, mixed>
     */
    public function list(string $column, array $conditions = []): array;
    /**
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>
     */
    public function assoc(int|array $conditions = [], string $idColumn = 'id'): array;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $pagingParams
     * @return array<int, array<string, mixed>>
     */
    public function records(array $conditions = [], array $pagingParams = []): array;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $pagingParams
     * @return array<mixed, mixed>
     */
    public function map(string $keyColumn, string $valueColumn, array $conditions = [], array $pagingParams = []): array;
    /**
     * @param array<string, mixed> $records
     */
    public function insert(array $records): int;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $newData
     */
    public function update(array $conditions, array $newData): int;
    /**
     * @param array<string, mixed> $conditions
     */
    public function delete(array $conditions = []): int;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public function replace(array $conditions, array $records): int;
}

interface ResultInterface
{
    /**
     * @param array<mixed, mixed> $data
     * @param array<string, string> $headers
     */
    public function redirect(callable $method, array $data = [], ?int $responseCode = null, $headers = []): ResultInterface;
    public function isRedirect(): bool;
    /**
     * @param array<string, mixed> $data
     */
    public function withData(array $data): ResultInterface;
    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): ResultInterface;
    public function withResponseCode(int $responseCode): ResultInterface;
    /**
     * @return array<string, mixed>
     */
    public function getData(): array;
    /**
     * @return array<string, string> $headers
     */
    public function getHeaders(): array;
    public function getResponseCode(): int;
    public function applyHeadersToResponse(ResponseInterface $response, RoutingInterface $routing): ResponseInterface;
}

interface SQLInterface
{
    /**
     * @param string|array<int|string, string> $fields
     */
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
    /**
     * @param array<string, mixed> $conditions
     */
    public function where(array $conditions = [], ?string $table = null, string $operator = ''): SQLInterface;
    public function eq(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function lt(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function lte(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function gt(string $column, mixed $value, ?string $table = null): SQLInterface;
    public function gte(string $column, mixed $value, ?string $table = null): SQLInterface;
    /**
     * @param string|array<int|string, string> $groupBy
     */
    public function group(string|array $groupBy): SQLInterface;
    /**
     * @param array<string, mixed> $more
     */
    public function order(string $sort, ?string $maybeDesc = null, array $more = []): SQLInterface;
    public function random(): SQLInterface;
    public function limit(int $per_page, int $page = 1): SQLInterface;
    /**
     * @param array<string, mixed> $pagingParams
     */
    public function orderLimit(array $pagingParams): SQLInterface;
    public function value(): mixed;
    /**
     * @return array<int, mixed>
     */
    public function list(): array;
    /**
     * @return array<string, mixed>
     */
    public function assoc(): array;
    /**
     * @return array<int, array<string, mixed>>
     */
    public function assocList(): array;
    /**
     * @return array<mixed, mixed>
     */
    public function map(): array;
    /**
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public function insert(string $table, array $records): int;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $newData
     */
    public function update(string $table, array $conditions, array $newData): int;
    /**
     * @param array<string, mixed> $conditions
     */
    public function delete(string $table, array $conditions = []): int;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public function replace(string $table, array $conditions, array $records): int;
    /**
     * @param array<string, mixed> $conditions
     */
    public function setBlob(string $table, string $column, string $blob, array $conditions = []): int;
}

interface ViewsInterface
{
    /**
     * @param array<string, mixed> $methodData
     * @param array<string, mixed> $globalData
     */
    public function render(string $controllerName, string $methodName, array $methodData, array $globalData, RoutingInterface $routing, ResponseInterface $response): ResponseInterface;
}
