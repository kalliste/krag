<?php

namespace Krag;

class SQL
{

    public function __construct(private DB $db) {}

    public function escape(string|array $toEscape) : string
    {
        return $this->db->escape($toEscape);
    }

    public function columnEscape(string|array $toEscape, ?string $table = null) : string
    {
        return $this->db->columnEscape($toEscape, $table);
    }

    public function tableEscape(string $toEscape) : string
    {
        return $this->db->tableEscape($toEscape);
    }

    public function fieldValue(string $key, $value, ?string $table = null, $operator = '=') : string
    {
        $key = $this->columnEscape($key);
        $value = $this->escape($value);
        if ($table)
        {
            $table = $this->tableEscape($table);
            return $table.'.'.$key.$operator."'".$value."'";
        }
        return $key.$operator."'".$value."'";
    }

    public function fieldsValues(array $conditions, ?string $table = null, $operator = '=') : string
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

    public function select(string|array $fields = [], ?string $table = null, bool $additional = false)
    {
        $ret = ($additional) ? ', ' : 'SELECT ';
        if (is_string($fields))
        {
            $fields = [$fields];
        }
        if (count($fields))
        {
            $ret .= $this->columnEscape($fields, $table);
        }
        return $ret;
    }

    public function selectAliased(string|array $fields = [], ?string $table = null, bool $additional = false)
    {
        $ret = ($additional) ? ', ' : 'SELECT ';
        if (is_string($fields))
        {
            $fields = [$fields];
        }
        if (count($fields))
        {
            $fields = array_map(function($field) { preg_replace('/[^A-Za-z0-9_]/', '', $field); });
            $first = true;
            foreach ($fields as $field => $alias)
            {
                $ret .= $first ? '' : ', ';
                $ret .= $this->columnEscape($field, $table);
                $ret .= ' AS '.$alias;
            }
        }
        return $ret;
    }

    public function where(array $conditions = [], ?string $table = null, bool $additional = false, $operator = '') : string
    {
        $where = ($additional) ? ' ' : ' WHERE (1=1) ';
        if (count($conditions))
        {
            $keyVal = $this->fieldsValues($conditions, $table, $operator);
            $where .= ' AND ('.implode(') AND (', $keyVal).') ';
        }
        return $where;
    }

    public function eq(string $column, mixed $value, ?string $table = null) : string
    {
        return $this->where(array($column => $value), $table, true);
    }

    public function lt(string $column, mixed $value, ?string $table = null) : string
    {
        return $this->where(array($column => $value), $table, true, '<');
    }

    public function lte(string $column, mixed $value, ?string $table = null) : string
    {
        return $this->where(array($column => $value), $table, true, '<=');
    }

    public function gt(string $column, mixed $value, ?string $table = null) : string
    {
        return $this->where(array($column => $value), $table, true, '>');
    }

    public function gte(string $column, mixed $value, ?string $table = null) : string
    {
        return $this->where(array($column => $value), $table, true, '>=');
    }

    public function group(string|array $groupBy) : string
    {
        $cols = (is_array($groupBy)) ? $groupBy : array($groupBy);
        return 'GROUP BY '.$this->columnEscape($cols);
    }

    private function orderPart(string $sort, ?string $maybeDesc = null) : string
    {
        $sort = $this->columnEscape($sort);
        return ($maybeDesc) ? $sort.' ' : $sort.' DESC';
    }

    public function order(string $sort, ?string $maybeDesc = null, ...$more) : string
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
            $ret .= $this->orderPart($v, $maybeDesc);
        }
        return $ret;
    }

    public function limit(int $per_page, int $page = 1) : string
    {
        $start = strval($per_page * ($page - 1));
        $count = strval($per_page);
        return 'LIMIT '.$start.', '.$count.' ';
    }

    public function value($query) : mixed
    {
        $result = $this->db->query($query);
        $row = $this->db->fetchRow($result);
        return $row[0];
    }

    public function list(string $query) : array
    {
        $result = $this->db->query($query);
        $ret = [];
        while ($row = $this->db->fetchRow($result))
        {
            $ret[] = $row[0];
        }
        return $ret;
    }

    function assoc(string $query) : array
    {
        $result = $this->db->query($query);
        return $this->db->fetchAssoc($result);
    }

    function assocList(string $query) : array
    {
        $result = $this->db->query($query);
        $ret = [];
        while ($row = $this->db-fetchAssoc($result)) {
            $ret[] = $row;
        }
        return $ret;
    }

    function map(string $query) : array
    {
        $result = $this->db->query($query);
        $ret = [];
        while ($row = $this->db->fetchRow($result)) {
            $ret[$row[0]] = $row[1];
        }
        return $ret;
    }

    private function deleteSQL(string $table, array $conditions = []) : string
    {
        $table = $this->tableEscape($table);
        return 'DELETE FROM '.$table.$this->where($conditions);
    }

    private function insertSQL(string $table, array $records) : string
    {
        $table = $this->tableEscape($table);
        $columns = array_keys(reset($records));
        $line = 'INSERT INTO '.$table.' ('.$this->columnEscape($columns).') VALUES ';
        $first = true;
        foreach ($records as $record) {
            if (!$first) {
                $line .= ', ';
            }
            $first = false;
            $line .= "('".implode("', '", $this->escape($record))."')";
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
            $table = $this->tableEscape($table);
            $keyVal = $this->fieldsValues($newData);
            $where = $this->where($conditions);
            $query = 'UPDATE '.$table.' SET '.$keyVal.$where;
            $result = $this->db->query($query);
            return $this->db->affectedRows($result);
        }
        return 0;
    }

    public function replace(string $table, array $conditions, $records) : int
    {
        $queries = [
            $this->deleteSQL($table, $conditions),
            $this->insertSQL($table, $records)
        ];
        return $this->transactionForLines($queries, false);
    }

    public function setBlob(string $table, string $column, string $blob, array $conditions = []) : int
    {
        $table = $this->tableEscape($table);
        $column = $this->columnEscape($column);
        $where = $this->where($conditions);
        $query = 'UPDATE '.$table.' SET '.$column.' = ? '.$where;
        $result = $this->db->setBlob($query, $blob);
        return $this-db>affectedRows($result);
    }

}

?>
