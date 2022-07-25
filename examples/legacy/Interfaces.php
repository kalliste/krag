<?php

namespace Krag;

interface StaticModelInterface
{
    /**
     * @param array<string, mixed> $conditions
     */
    public static function value(string $column, array $conditions = []): mixed;
    /**
     * @param array<string, mixed> $conditions
     * @return array<int, mixed>
     */
    public static function list(string $column, array $conditions = []): array;
    /**
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>
     */
    public static function assoc(int|array $conditions = [], string $idColumn = 'id'): array;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $pagingParams
     * @return array<int, array<string, mixed>>
     */
    public static function records(array $conditions = [], ?array $pagingParams = null): array;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $pagingParams
     * @return array<mixed, mixed>
     */
    public static function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null): array;
    /**
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public static function insert(array $records): int;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $newData
     */
    public static function update(array $conditions, array $newData): int;
    /**
     * @param array<string, mixed> $conditions
     */
    public static function delete(array $conditions = []): int;
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public static function replace(array $conditions, array $records): int;
}
