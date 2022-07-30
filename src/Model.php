<?php

namespace Krag;

// FIXME add a Trait that handles in and out filters

/**
 *
 */
class Model implements ModelInterface
{
    private string $table;

    public function __construct(private readonly SQLInterface $sql, ?string $table = null)
    {
        $this->table = $table ?? strtolower(static::class);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public function value(string $column, array $conditions = []): mixed
    {
        return $this->sql->select($column)->from($this->table)->where($conditions)->value();
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<int, mixed>
     */
    public function list(string $column, array $conditions = []): array
    {
        return $this->sql->select($column)->from($this->table)->where($conditions)->list();
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>
     */
    public function assoc(int|array $conditions = [], string $idColumn = 'id'): array
    {
        $conditions = (is_int($conditions)) ? [$idColumn => $conditions] : $conditions;
        return $this->sql->select()->from($this->table)->where($conditions)->assoc();
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $pagingParams
     * @return array<int, array<string, mixed>>
     */
    public function records(array $conditions = [], array $pagingParams = []): array
    {
        return $this->sql->select()->from($this->table)->where($conditions)->orderLimit($pagingParams)->assocList();
    }

    /**
     * @param string $keyColumn
     * @param string $valueColumn
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $pagingParams
     * @return array<mixed, mixed>
     */
    public function map(string $keyColumn, string $valueColumn, array $conditions = [], array $pagingParams = []): array
    {
        return $this->sql->select([$keyColumn, $valueColumn])->from($this->table)->where($conditions)->orderLimit($pagingParams)->map();
    }

    /**
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public function insert(array $records): int
    {
        return $this->sql->insert($this->table, $records);
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $newData
     */
    public function update(array $conditions, array $newData): int
    {
        return $this->sql->update($this->table, $conditions, $newData);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public function delete(array $conditions = []): int
    {
        return $this->sql->delete($this->table, $conditions);
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed>|array<int|string, array<string, mixed>> $records
     */
    public function replace(array $conditions, array $records): int
    {
        return $this->sql->replace($this->table, $conditions, $records);
    }
}
