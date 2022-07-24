<?php

namespace Krag;

class Model implements ModelInterface
{
    public function __construct(private SQLInterface $sql, private ?string $table = null)
    {
        if (!$table) {
            $this->table = strtolower(static::class);
        }
    }

    public function value(string $column, array $conditions = []): mixed
    {
        return $this->sql->select($column)->from($this->table)->where($conditions)->value();
    }

    public function list(string $column, array $conditions = []): array
    {
        return $this->sql->select($column)->from($this->table)->where($conditions)->list();
    }

    public function assoc(int|array $conditions = [], $idColumn = 'id'): array
    {
        $conditions = (is_int($conditions)) ? [$idColumn => $conditions] : $conditions;
        return $this->sql->select()->from($this->table)->where($conditions)->list();
    }

    public function records(array $conditions = [], ?array $pagingParams = null): array
    {
        return $this->sql->select()->from($this->table)->where($conditions)->orderLimit($pagingParams)->assocList();
    }

    public function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null): array
    {
        return $this->sql->select([$keyColumn, $valueColumn])->from($this->table)->where($conditions)->orderLimit($pagingParams)->map();
    }

    public function insert(array $records): int
    {
        return $this->sql->insert($this->table, $records);
    }

    public function update(array $conditions, array $newData): int
    {
        return $this->sql->update($this->table, $conditions, $newData);
    }

    public function delete(array $conditions = []): int
    {
        return $this->sql->delete($this->table, $conditions);
    }

    public function replace(array $conditions, array $records): int
    {
        return $this->sql->replace($this->table, $conditions, $records);
    }
}
