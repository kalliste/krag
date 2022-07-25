<?php

namespace Krag;

class LegacyModel extends StaticModel
{
    /**
     * @param array<string, mixed> $conditions
     * @return array<int, mixed>
     */
    public static function values(string $column, array $conditions = []): array
    {
        return self::list($column, $conditions);
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed>|bool $paging_params
     * @return array<mixed, mixed>
     */
    public static function hash(string $key_column, string $value_column, array $conditions = [], array|bool $paging_params = []): array
    {
        if (!$paging_params) {
            $paging_params = [];
        }
        return self::map($key_column, $value_column, $conditions, $paging_params);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public static function blob(string $column, array $conditions, string $blob): void
    {
        static::sql()->setBlob(static::table(), $column, $blob, $conditions);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public static function update($conditions, $newdata): int
    {
        if (!is_array($conditions)) {
            $conditions = ['id' => $conditions];
        }
        return static::sql()->update(static::table(), $conditions, $newdata);
    }

    /**
     * @param array<mixed, mixed> $records
     */
    public static function insert(array $records, mixed $y = ''): int
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
