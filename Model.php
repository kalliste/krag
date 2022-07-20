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
        $conditions = (is_int($conditions)) ? [$idColumn => $conditions] : $conditions;
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

class StaticModel
{

    private static ?InjectionInterface $injection = null;

    public static function getInjection(InjectionInterface $injection)
    {
        return StaticModel::$injection;
    }

    public static function setInjection(InjectionInterface $injection)
    {
        StaticModel::$injection = $injection;
    }

    private StaticModel function make() : Model
    {
        return StaticModel::getInjection()->make('Model', ['table' => static::class]);
    }

    public StaticModel function value(string $column, array $conditions = []) : mixed
    {
        return StaticModel::make()->value();
    }

    public StaticModel function list(string $column, array $conditions = []) : array
    {
        return StaticModel::make()->list($column, $conditions);
    }

    public StaticModel function assoc(int|array $conditions = [], $idColumn = 'id') : array
    {
        return StaticModel::make()->assoc($conditions, $idColumn);
    }

    public StaticModel function records(array $conditions = [], ?array $pagingParams = null) : array
    {
        return StaticModel::make()->records($conditions, $pagingParams);
    }

    public StaticModel function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null) : array
    {
        return StaticModel::make()->map($keyColumn, $valueColumn, $conditions, $pagingParams);
    }

    public StaticModel function insert(array $records) : int
    {
        return StaticModel::make()->insert($records);
    }

    public StaticModel function update(array $conditions, array $newData) : int
    {
        return StaticModel::make()->update($records, $newData);
    }

    public StaticModel function delete(array $conditions = []) : int
    {
        return StaticModel::make()->delete($conditions);
    }

    public StaticModel function replace(array $conditions, array $records) : int
    {
        return StaticModel::replace($conditions, $records);
    }

}
