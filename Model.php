<?php

namespace Krag;

class Model implements StaticModelInterface
{

    protected static ?InjectionInterface $injection = null;
    protected static ?string $table = null;

    public static function getInjection(InjectionInterface $injection)
    {
        return static::$injection;
    }

    public static function setInjection(InjectionInterface $injection)
    {
        static::$injection = $injection;
    }

    protected function sql() : SQLInterface
    {
        return static::injection->make('SQL');
    }

    protected function table() : string
    {
       return static::$table ?? strtolower(static::class);
    }

    public static function value(string $column, array $conditions = []) : mixed
    {
        return static::sql()->select($column)->from(static::table())->where($conditions)->value();
    }

    public static function list(string $column, array $conditions = []) : array
    {
        return static::sql()->select($column)->from(static::table())->where($conditions)->list();
    }

    public static function assoc(int|array $conditions = [], $idColumn = 'id') : array
    {
        $conditions = (is_int($conditions)) ? [$idColumn => $conditions] : $conditions;
        return static::sql()->select()->from(static::table())->where($conditions)->list();
    }

    public static function records(array $conditions = [], ?array $pagingParams = null) : array
    {
        return static::sql()->select()->from(static::table())->where($conditions)->orderLimit($pagingParams)->assocList();
    }

    public static function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null) : array
    {
        return static::sql()->select([$keyColumn, $valueColumn])->from(static::table())->where($conditions)->orderLimit($pagingParams)->map();
    }

    public static function insert(array $records) : int
    {
        return static::sql()->insert(static::table(), $records);
    }

    public static function update(array $conditions, array $newData) : int
    {
        return static::sql()->update(static::table(), $conditions, $newData);
    }

    public static function delete(array $conditions = []) : int
    {
        return static::sql()->delete(static::table(), $conditions);
    }

    public static function replace(array $conditions, array $records) : int
    {
        return static::sql()->replace(static::table(), $conditions, $records);
    }

}

?>
