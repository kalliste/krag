<?php

namespace Krag;

class LegacyModel extends StaticModel
{
    public static function values($column, $conditions = [])
    {
        return self::list($column, $conditions);
    }

    public static function hash($key_column, $value_column, $conditions = [], $paging_params = false)
    {
        return self::map($key_column, $value_column, $conditions, $paging_params);
    }

    public static function blob($column, $conditions, $blob)
    {
        static::sql()->setBlob(static::table(), $column, $blob, $conditions);
    }

    public static function update($conditions, $newdata): int
    {
        if (!is_array($conditions)) {
            $conditions = ['id' => $conditions];
        }
        return static::sql()->update(static::table(), $conditions, $newdata);
    }

    public static function insert(array $records, $y = ''): int
    {
        if (is_array($y)) {
            $conditions = $records;
            $records = [];
            foreach ($y as $record) {
                $records[] = array_merge($y, $conditions);
            }
        }
        return static::sql()->insert(static::table(), $records);
    }
}
