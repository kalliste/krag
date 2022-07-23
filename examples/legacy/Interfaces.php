<?php

namespace Krag;

interface StaticModelInterface
{
    public static function value(string $column, array $conditions = []) : mixed;
    public static function list(string $column, array $conditions = []) : array;
    public static function assoc(int|array $conditions = [], $idColumn = 'id') : array;
    public static function records(array $conditions = [], ?array $pagingParams = null) : array;
    public static function map(string $keyColumn, string $valueColumn, array $conditions = [], ?array $pagingParams = null) : array;
    public static function insert(array $records) : int;
    public static function update(array $conditions, array $newData) : int;
    public static function delete(array $conditions = []) : int;
    public static function replace(array $conditions, array $records) : int;
}

?>
