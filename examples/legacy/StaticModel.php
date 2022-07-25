<?php

namespace Krag;

class StaticModel implements StaticModelInterface
{
    protected static InjectionInterface $injection;
    protected static string $table;

    public static function getInjection(): InjectionInterface
    {
        return static::$injection;
    }

    public static function setInjection(InjectionInterface $injection): void
    {
        static::$injection = $injection;
    }

    protected static function sql(): SQLInterface
    {
        return static::$injection->get('SQLInterface');
    }

    protected static function table(): string
    {
        return static::$table ?? strtolower(static::class);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public static function value(string $column, array $conditions = []): mixed
    {
        return static::sql()->select($column)->from(static::table())->where($conditions)->value();
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<int, mixed>
     */
    public static function list(string $column, array $conditions = []): array
    {
        return static::sql()->select($column)->from(static::table())->where($conditions)->list();
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>
     */
    public static function assoc(int|array $conditions = [], string $idColumn = 'id'): array
    {
        $conditions = (is_int($conditions)) ? [$idColumn => $conditions] : $conditions;
        return static::sql()->select()->from(static::table())->where($conditions)->assoc();
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $pagingParams
     * @return array<int, array<string, mixed>>
     */
    public static function records(array $conditions = [], ?array $pagingParams = null): array
    {
        return static::sql()->select()->from(static::table())->where($conditions)->orderLimit($pagingParams)->assocList();
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $pagingParams
     * @return array<mixed, mixed>
     */
    public static function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null): array
    {
        return static::sql()->select([$keyColumn, $valueColumn])->from(static::table())->where($conditions)->orderLimit($pagingParams)->map();
    }

    /**
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public static function insert(array $records): int
    {
        return static::sql()->insert(static::table(), $records);
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $newData
     */
    public static function update(array $conditions, array $newData): int
    {
        return static::sql()->update(static::table(), $conditions, $newData);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public static function delete(array $conditions = []): int
    {
        return static::sql()->delete(static::table(), $conditions);
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public static function replace(array $conditions, array $records): int
    {
        return static::sql()->replace(static::table(), $conditions, $records);
    }
}
