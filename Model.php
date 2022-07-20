<?php

namespace Krag;

class Model
{

    public function __construct(private InjectionInterface $injection, private string $table) {}

    protected function sql() : SQLInterface
    {
        return $this->injection->make('SQL');
    }

    public function value(string $column, array $conditions = []) : mixed
    {
        return $this->sql()->select($column)->from($this->table)->where($conditions)->value();
    }

    public function list(string $column, array $conditions = []) : array
    {
        return $this->sql()->select($column)->from($this->table)->where($conditions)->list();
    }

    public function assoc(int|array $conditions = [], $idColumn = 'id') : array
    {
        $conditions = (is_int($conditions) ? [$idColumn => $conditions] : $conditions;
        return $this->sql()->select()->from($this->table)->where($conditions)->list();
    }

    public function records(array $conditions = [], ?array $pagingParams = null) : array
    {
        return $this->sql()->select()->from($this->table)->where($conditions)->orderLimit($pagingParams)->assocList();
    }

    public function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null) : array
    {
        return $this->sql()->select([$keyColumn, $valueColumn])->from($this->table)->where($conditions)->orderLimit($pagingParams)->map();
    }

    public function insert(array $records) : int
    {
        return $this->sql()->insert($this->table, $records);
    }

    public function update(array $conditions, array $newData) : int
    {
        return $this->sql()->update($this->table, $conditions, $newData);
    }

    public function delete(array $conditions = []) : int
    {
        return $this->sql()->delete($this->table, $conditions);
    }

    public function replace(array $conditions, array $records) : int
    {
        return $this->sql()->replace($this->table, $conditions, $records);
    }

}

trait staticModel
{

    private static function make() : object
    {
        return Injection::getInstance()->make(static::class);
    }

    public static function value(string $column, array $conditions = []) : mixed
    {
        return static::make()->value();
    }

    public static function list(string $column, array $conditions = []) : array
    {
        return static::make()->list($column, $conditions);
    }

    public static function assoc(int|array $conditions = [], $idColumn = 'id') : array
    {
        return static::make()->assoc($conditions, $idColumn);
    }

    public static function records(array $conditions = [], ?array $pagingParams = null) : array
    {
        return static::make()->records($conditions, $pagingParams);
    }

    public static function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null) : array
    {
        return static::make()->map($keyColumn, $valueColumn, $conditions, $pagingParams);
    }

    public static function insert(array $records) : int
    {
        return static::make()->insert($records);
    }

    public static function update(array $conditions, array $newData) : int
    {
        return static::make()->update($records, $newData);
    }

    public static function delete(array $conditions = []) : int
    {
        return static::make()->delete($conditions);
    }

    public static function replace(array $conditions, array $records) : int
    {
        return static::replace($conditions, $records);
    }

}
