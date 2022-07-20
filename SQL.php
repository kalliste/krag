<?php

namespace Krag;

class SQL implements SQLInterface
{

    // FIXME: this should be $verb $from $join $where $group $order $limit so we can build out of order
    private string $query = '';
    private bool $haveSelect = false;
    private bool $haveWhere = false;

    public function __construct(private DB $db) {}

    /************************************************************************/

    private function fieldValue(string $key, $value, ?string $table = null, string $operator = '=') : string
    {
        $key = $this->db->columnEscape($key);
        $value = $this->db->escape($value);
        if ($table)
        {
            $table = $this->db->tableEscape($table);
            return $table.'.'.$key.$operator."'".$value."'";
        }
        return $key.$operator."'".$value."'";
    }

    private function fieldsValues(array $conditions, ?string $table = null, string $operator = '=') : string
    {
        $ret = '';
        $first = true;
        foreach ($conditions as $k => $v)
        {
            if (!$first)
            {
                $ret .= ', ';
            }
            $first = false;
            $ret .= $this->fieldValue($k, $v, $table);
        }
        return $ret;
    }

    private function deleteSQL(string $table, array $conditions = []) : string
    {
        $table = $this->db->tableEscape($table);
        return 'DELETE FROM '.$table.$this->where($conditions);
    }

    private function insertSQL(string $table, array $records) : string
    {
        $table = $this->db->tableEscape($table);
        $columns = array_keys(reset($records));
        $line = 'INSERT INTO '.$table.' ('.$this->db->columnEscape($columns).') VALUES ';
        $first = true;
        foreach ($records as $record) {
            if (!$first) {
                $line .= ', ';
            }
            $first = false;
            $line .= "('".implode("', '", $this->db->escape($record))."')";
        }
        return $line;
    }

    private function transactionForLines(array $lines, bool $returnLastInsertId = true) : int
    {
        $this->db->begin();
        foreach ($lines as $line) {
            $result = $this->db->query($line);
        }
        if ($returnLastInsertId) {
            $ret = $this->db->insertId();
        }
        $this->db->commit();
        if (!$returnLastInsertId) {
            $ret = $this->db->affectedRows($result);
        }
        return $ret;
    }

    /************************************************************************/

    public function select(string|array $fields = [], ?string $table = null) : SQL
    {
        if (!$this->haveSelect)
        {
            $ret .= 'SELECT ';
            $this->haveSelect = true;
        }
        $fields = (is_string($fields)) ? [$fields] : $fields;
        if (count($fields))
        {
            $ret .= $this->db->columnEscape($fields, $table).' ';
        }
        $this->query .= $ret;
        return $this;
    }

    public function selectAliased(string|array $fields = [], ?string $table = null) : SQL
    {
        if (!$this->haveSelect)
        {
            $ret .= 'SELECT ';
            $this->haveSelect = true;
        }
        $fields = (is_string($fields)) ? [$fields] : $fields;
        if (count($fields))
        {
            $fields = array_map($this->db->aliasEscape(...), $fields);
            $first = true;
            foreach ($fields as $field => $alias)
            {
                $ret .= $first ? '' : ', ';
                $ret .= $this->db->columnEscape($field, $table);
                $ret .= ' AS '.$alias.' ';
                $first = false;
            }
        }
        $this->query .= $ret;
        return $this;
    }

    public function from(string $table, ?string $alias = null) : SQL
    {
        // FIXME: don't use haveSelect here once we split the internal representation
        if (!$this->haveSelect)
        {
            $ret .= 'SELECT ';
            $this->haveSelect = true;
        }
        $ret = ' FROM '.$this->db->tableEscape($table);
        $ret .= (is_string($alias)) ? ' AS '.$this->db->aliasEscape($alias) : '';
        $this->query .= $ret;
        return $this;
    }

    public function left() : SQL
    {
        $this->query .= ' LEFT ';
        return $this;
    }

    public function right() : SQL
    {
        $this->query .= ' RIGHT ';
        return $this;
    }

    public function inner() : SQL
    {
        $this->query .= ' INNER ';
        return $this;
    }

    public function outer() : SQL
    {
        $this->query .= ' OUTER ';
        return $this;
    }

    public function cross() : SQL
    {
        $this->query .= ' CROSS ';
        return $this;
    }

    public function natural() : SQL
    {
        $this->query .= ' NATURAL ';
        return $this;
    }

    public function join(string $table, ?string $alias = null) : SQL
    {
        $ret = ' JOIN '.$this->db->tableEscape($table);
        $ret .= (is_string($alias)) ? ' AS '.$this->db->aliasEscape($alias) : '';
        $this->query .= $ret;
        return $this;
    }

    public function where(array $conditions = [], ?string $table = null, string $operator = '') : SQL
    {
        if (count($conditions))
        {
            $ret = '';
            if ($this->haveWhere)
            {
                $ret .= ' AND ';
            }
            else
            {
                $ret .= ' WHERE ';
                $this->haveWhere = true;
            }
            $keyVal = $this->fieldsValues($conditions, $table, $operator);
            $ret .= ' ('.implode(') AND (', $keyVal).') ';
            $this->query .= $ret;

        }
        return $this;
    }

    public function eq(string $column, mixed $value, ?string $table = null) : SQL
    {
        return $this->where(array($column => $value), $table);
    }

    public function lt(string $column, mixed $value, ?string $table = null) : SQL
    {
        return $this->where(array($column => $value), $table, '<');
    }

    public function lte(string $column, mixed $value, ?string $table = null) : SQL
    {
        return $this->where(array($column => $value), $table, '<=');
    }

    public function gt(string $column, mixed $value, ?string $table = null) : SQL
    {
        return $this->where(array($column => $value), $table, '>');
    }

    public function gte(string $column, mixed $value, ?string $table = null) : SQL
    {
        return $this->where(array($column => $value), $table, '>=');
    }

    public function group(string|array $groupBy) : SQL
    {
        $cols = (is_array($groupBy)) ? $groupBy : array($groupBy);
        $ret = ' GROUP BY '.$this->db->columnEscape($cols);
        $this->query .= $ret;
        return $this;
    }

    private function orderPart(string $sort, ?string $maybeDesc = null) : string
    {
        $sort = $this->db->columnEscape($sort);
        return ($maybeDesc) ? $sort.' DESC' : $sort.' ';
    }

    public function order(string $sort, ?string $maybeDesc = null, ...$more) : SQL
    {
        $ret = ' ORDER BY '.$this->orderPart($sort, $maybeDesc);
        $moreSorts = [];
        foreach ($more as $k => $v)
        {
            if ('sort' == substr($k, 0, 4))
            {
                $which = intval(substr($k, 4));
                $moreSorts[$which] = $v;
            }
        }
        ksort($moreSorts);
        foreach ($moreSorts as $k => $v)
        {
            $descColumn = 'order'.substr($k, 4);
            $maybeDesc = array_key_exists($descColumn, $more) ? $more[$descColumn] : '';
            $ret .= ', '.$this->orderPart($v, $maybeDesc);
        }
        $this->query .= $ret;
        return $this;
    }

    public function limit(int $per_page, int $page = 1) : SQL
    {
        $start = strval($per_page * ($page - 1));
        $count = strval($per_page);
        $ret = ' LIMIT '.$start.', '.$count.' ';
        $this->query .= $ret;
        return $this;
    }

    public function orderLimit(array $pagingParams) : SQL
    {
        $ret = $this;
        if (array_key_exists('sort', $pagingParams))
        {
            $sort = $pagingParams['sort'];
            $maybeDesc = $pagingParams['order'] ?? null;
            $more = array_filter(function($k)
            {
                return (('sort' == substr($k, 0, 4)) && intval(substr($k, 4)) > 0);
            }, $pagingParams);
            $ret = $ret->order($sort, $maybeDesc, ...$more);
        }
        if (array_key_exists('per_page', $pagingParams))
        {
            $page = $pagingParams['page'] ?? 1;
            $ret = $ret->limit($per_page, $page);
        }
        return $ret;
    }

    /************************************************************************/

    public function value() : mixed
    {
        $result = $this->db->query($this->query);
        $row = $this->db->fetchRow($result);
        return $row[0];
    }

    public function list() : array
    {
        $result = $this->db->query($this->query);
        $ret = [];
        while ($row = $this->db->fetchRow($result))
        {
            $ret[] = $row[0];
        }
        return $ret;
    }

    public function assoc() : array
    {
        $result = $this->db->query($this->query);
        return $this->db->fetchAssoc($result);
    }

    public function assocList() : array
    {
        $result = $this->db->query($this->query);
        $ret = [];
        while ($row = $this->db-fetchAssoc($result)) {
            $ret[] = $row;
        }
        return $ret;
    }

    public function map() : array
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
    // Probably add a do() function and make the $conditions arguments optional

    public function insert(string $table, array $records) : int
    {
        if (count($records))
        {
            $result = $this->db->query($this->insertSQL($table, $records));
            return $this->db->affectedRows($result);
        }
        return 0;
    }

    public function update(string $table, array $conditions, array $newData) : int
    {
        if (count($newData))
        {
            $table = $this->db->tableEscape($table);
            $keyVal = $this->fieldsValues($newData);
            $where = $this->where($conditions);
            $query = 'UPDATE '.$table.' SET '.$keyVal.$where;
            $result = $this->db->query($query);
            return $this->db->affectedRows($result);
        }
        return 0;
    }

    public function delete(string $table, array $conditions = []) : int
    {
        $result = $this->db->query($this->deleteSQL($table, $conditions));
        return $this->db->affectedRows($result);
    }

    public function replace(string $table, array $conditions, array $records) : int
    {
        $queries = [
            $this->deleteSQL($table, $conditions),
            $this->insertSQL($table, $records)
        ];
        return $this->transactionForLines($queries, false);
    }

    public function setBlob(string $table, string $column, string $blob, array $conditions = []) : int
    {
        $table = $this->db->tableEscape($table);
        $column = $this->db->columnEscape($column);
        $where = $this->where($conditions);
        $query = 'UPDATE '.$table.' SET '.$column.' = ? '.$where;
        $result = $this->db->setBlob($query, $blob);
        return $this-db>affectedRows($result);
    }

}

?>
