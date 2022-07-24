<?php

namespace Krag;

class SQL implements SQLInterface
{
    // FIXME: this should be $verb $from $join $where $group $order $limit so we can build out of order
    protected string $query = '';
    protected bool $haveSelect = false;
    protected bool $haveWhere = false;

    public function __construct(private DB $db)
    {
    }

    /************************************************************************/

    protected function fieldValue(string $key, $value, ?string $table = null, string $operator = '='): string
    {
        $key = $this->db->columnEscape($key);
        $value = $this->db->escape($value);
        if ($table) {
            $table = $this->db->tableEscape($table);
            return $table.'.'.$key.$operator."'".$value."'";
        }
        return $key.$operator."'".$value."'";
    }

    protected function fieldsValues(array $conditions, ?string $table = null, string $operator = '='): string
    {
        $ret = '';
        $first = true;
        foreach ($conditions as $k => $v) {
            $ret .= ($first) ? '' : ', ';
            $ret .= $this->fieldValue($k, $v, $table);
            $first = false;
        }
        return $ret;
    }

    protected function splitByComma(string $str): array
    {
        return array_diff(array_map(trim(...), explode(',', $str)), ['']);
    }

    /************************************************************************/

    public function select(string|array $fields = [], ?string $table = null): SQL
    {
        $ret = '';
        if (!$this->haveSelect) {
            $ret .= 'SELECT ';
            $this->haveSelect = true;
        }
        $fields = (is_string($fields)) ? $this->splitByComma($fields) : $fields;
        if (array_is_list($fields)) {
            $ret .= (count($fields)) ? $this->db->columnEscape($fields, $table).' ' : '* ';
        } else {
            $fields = array_map($this->db->aliasEscape(...), $fields);
            $first = true;
            foreach ($fields as $field => $alias) {
                $ret .= $first ? '' : ', ';
                $ret .= $this->db->columnEscape($field, $table).' AS '.$alias.' ';
                $first = false;
            }
        }
        $this->query .= $ret;
        return $this;
    }

    public function count(?string $field = null, ?string $table = null, ?string $alias = null): SQL
    {
        $field = (is_null($field)) ? '*' : $this->db->columnEscape($field);
        $table = (is_null($table)) ? '' : '.'.$this->db->tableEscape($table);
        $as = (is_null($alias)) ? '' : ' AS '.$this->db->aliasEscape($alias).' ';
        $this->query .= ' COUNT('.$table.$field.') '.$as;
        return $this;
    }

    public function from(string $table, ?string $alias = null): SQL
    {
        $ret = '';
        // FIXME: don't use haveSelect here once we split the internal representation
        if (!$this->haveSelect) {
            $ret .= 'SELECT ';
            $this->haveSelect = true;
        }
        $ret = ' FROM '.$this->db->tableEscape($table);
        $ret .= (is_string($alias)) ? ' AS '.$this->db->aliasEscape($alias) : '';
        $this->query .= $ret;
        return $this;
    }

    public function left(): SQL
    {
        $this->query .= ' LEFT ';
        return $this;
    }

    public function right(): SQL
    {
        $this->query .= ' RIGHT ';
        return $this;
    }

    public function inner(): SQL
    {
        $this->query .= ' INNER ';
        return $this;
    }

    public function outer(): SQL
    {
        $this->query .= ' OUTER ';
        return $this;
    }

    public function cross(): SQL
    {
        $this->query .= ' CROSS ';
        return $this;
    }

    public function natural(): SQL
    {
        $this->query .= ' NATURAL ';
        return $this;
    }

    public function join(string $table, ?string $alias = null): SQL
    {
        $ret = ' JOIN '.$this->db->tableEscape($table);
        $ret .= (is_string($alias)) ? ' AS '.$this->db->aliasEscape($alias) : '';
        $this->query .= $ret;
        return $this;
    }

    public function where(array $conditions = [], ?string $table = null, string $operator = ''): SQL
    {
        if (count($conditions)) {
            $ret = '';
            if ($this->haveWhere) {
                $ret .= ' AND ';
            } else {
                $ret .= ' WHERE ';
                $this->haveWhere = true;
            }
            $ret .= '('.$this->fieldsValues($conditions, $table, $operator).')';
            $this->query .= $ret;
        }
        return $this;
    }

    public function eq(string $column, mixed $value, ?string $table = null): SQL
    {
        return $this->where(array($column => $value), $table);
    }

    public function lt(string $column, mixed $value, ?string $table = null): SQL
    {
        return $this->where(array($column => $value), $table, '<');
    }

    public function lte(string $column, mixed $value, ?string $table = null): SQL
    {
        return $this->where(array($column => $value), $table, '<=');
    }

    public function gt(string $column, mixed $value, ?string $table = null): SQL
    {
        return $this->where(array($column => $value), $table, '>');
    }

    public function gte(string $column, mixed $value, ?string $table = null): SQL
    {
        return $this->where(array($column => $value), $table, '>=');
    }

    public function group(string|array $groupBy): SQL
    {
        $cols = (is_array($groupBy)) ? $groupBy : array($groupBy);
        $ret = ' GROUP BY '.$this->db->columnEscape($cols);
        $this->query .= $ret;
        return $this;
    }

    private function orderPart(string $sort, ?string $maybeDesc = null): string
    {
        $sort = $this->db->columnEscape($sort);
        return ($maybeDesc) ? $sort.' DESC' : $sort.' ';
    }

    public function order(string $sort, ?string $maybeDesc = null, ...$more): SQL
    {
        $ret = ' ORDER BY '.$this->orderPart($sort, $maybeDesc);
        $moreSorts = [];
        foreach ($more as $k => $v) {
            if ('sort' == substr($k, 0, 4)) {
                $which = 'sort'.strval(intval(substr($k, 4)));
                $moreSorts[$which] = $v;
            }
        }
        ksort($moreSorts);
        foreach ($moreSorts as $k => $v) {
            $descColumn = 'order'.substr($k, 4);
            $maybeDesc = array_key_exists($descColumn, $more) ? $more[$descColumn] : '';
            $ret .= ', '.$this->orderPart($v, $maybeDesc);
        }
        $this->query .= $ret;
        return $this;
    }

    public function random() : SQL
    {
        $this->query .= ' '.$this->db->randomFuncSQL.' ';
        return $this;
    }

    public function limit(int $per_page, int $page = 1): SQL
    {
        $start = strval($per_page * ($page - 1));
        $count = strval($per_page);
        $ret = ' LIMIT '.$start.', '.$count.' ';
        $this->query .= $ret;
        return $this;
    }

    public function orderLimit(array $pagingParams): SQL
    {
        $ret = $this;
        if (array_key_exists('sort', $pagingParams)) {
            $sort = $pagingParams['sort'];
            $maybeDesc = $pagingParams['order'] ?? null;
            $more = array_filter(
                $pagingParams,
                fn($k) => (
                    ('sort' == substr($k, 0, 4)) && (intval(substr($k, 4)) > 0) ||
                    ('order' == substr($k, 0, 4)) && (intval(substr($k, 4)) > 0)
                )
            );
            $ret = $ret->order($sort, $maybeDesc, ...$more);
        }
        if (array_key_exists('per_page', $pagingParams)) {
            $page = $pagingParams['page'] ?? 1;
            $perPage = $pagingParams['page'] ?? 1;
            $ret = $ret->limit($perPage, $page);
        }
        return $ret;
    }

    /************************************************************************/

    public function value(): mixed
    {
        $result = $this->db->query($this->query);
        $row = $this->db->fetchRow($result);
        return $row[0];
    }

    public function list(): array
    {
        $result = $this->db->query($this->query);
        $ret = [];
        while ($row = $this->db->fetchRow($result)) {
            $ret[] = $row[0];
        }
        return $ret;
    }

    public function assoc(): array
    {
        $result = $this->db->query($this->query);
        return $this->db->fetchAssoc($result);
    }

    public function assocList(): array
    {
        $result = $this->db->query($this->query);
        $ret = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $ret[] = $row;
        }
        return $ret;
    }

    public function map(): array
    {
        $result = $this->db->query($this->query);
        $ret = [];
        while ($row = $this->db->fetchRow($result)) {
            $ret[$row[0]] = $row[1];
        }
        return $ret;
    }

    /************************************************************************/

    // FIXME: allow combining these with all applicable SQL part functions above
    // probably by making them have to be at the end of the chain.
    // Also be sure to allow INSERT SELECT

    public function insert(string $table, array $records): int
    {
        if (count($records)) {
            $records = (is_array(reset($records))) ? $records : [$records];
            $table = $this->db->tableEscape($table);
            $columns = array_keys(reset($records));
            $query = 'INSERT INTO '.$table.' ('.$this->db->columnEscape($columns).') VALUES ';
            $first = true;
            foreach ($records as $record) {
                if (!$first) {
                    $query .= ', ';
                }
                $first = false;
                $query .= "('".implode("', '", $this->db->escape($record))."')";
            }
            $result = $this->db->query($query);
            return $this->db->affectedRows($result);
        }
        return 0;
    }

    public function update(string $table, array $conditions, array $newData): int
    {
        if (count($newData)) {
            $table = $this->db->tableEscape($table);
            $keyVal = $this->fieldsValues($newData);
            $query = 'UPDATE '.$table.' SET '.$keyVal;
            $where = $this->where($conditions);
            $result = $this->db->query($query);
            return $this->db->affectedRows($result);
        }
        return 0;
    }

    public function delete(string $table, array $conditions = []): int
    {
        $table = $this->db->tableEscape($table);
        $query = 'DELETE FROM '.$table;
        $this->where($conditions);
        $result = $this->db->query($query);
        return $this->db->affectedRows($result);
    }

    public function replace(string $table, array $conditions, array $records): int
    {
        $this->db->begin();
        $this->delete($table, $conditions);
        $affected = $this->insert($table, $records);
        $this->db->commit();
        return $affected;
    }

    public function setBlob(string $table, string $column, string $blob, array $conditions = []): int
    {
        $table = $this->db->tableEscape($table);
        $column = $this->db->columnEscape($column);
        $query = 'UPDATE '.$table.' SET '.$column.' = ? ';
        $this->where($conditions);
        $result = $this->db->setBlob($query, $blob);
        return $this->db->affectedRows($result);
    }
}
