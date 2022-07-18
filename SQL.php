<?php

namespace Krag;

class SQL
{

    public function __construct(
        private DB $db,
    ) {}

    public function columnKeyEqualsValue(array $item, string $key, string $table = '') : string
    {
        $c = $this->db->columnQuoteChar;
        if ($table)
        {
            return $c.$table.$c.".".$c.$key.$c."='".$item."'";
        }
        return $c.$key.$c."='".$item."'";
    }

    public function where(array $conditions = [], string $table = '') : string
    {
        if (count($conditions))
        {
            $keyEqualsVal = array_map([$this, 'columnKeyEqualsValue'], $conditions);
            return " WHERE (1=1) AND (".implode(") AND (", $keyEqualsVal).") ";
        }
        return " WHERE (1=1) ";
    }

    public function group(string|array $groupBy) : string
    {
        $c = $this->db->columnQuoteChar;
        $cols = (is_array($groupBy)) ? $groupBy : array($groupBy);
        return "GROUP BY ".$c.implode("$c, $c", $cols).$c;
    }

    private function orderPart(string $sort, ?string $maybeDesc = null) : string
    {
        $c = $this->db->columnQuoteChar;
        $ret = $c.$sort.$c." ";
        if ($maybeDesc)
        {
            $ret .= "DESC ";
        }
        return $ret;
    }

    public function order(string $sort, ?string $maybeDesc = null, ...$more) : string
    {
        $ret = " ORDER BY ".$this->orderPart($sort, $maybeDesc);
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
        return "LIMIT $start, $count ";
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
        $ret = array();
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
        $ret = array();
        while ($row = $this->db-fetchAssoc($result)) {
            $ret[] = $row;
        }
        return $ret;
    }

    function map(string $query) : array
    {
        $result = $this->db->query($query);
        $ret = array();
        while ($row = $this->db->fetchRow($result)) {
            $ret[$row[0]] = $row[1];
        }
        return $ret;
    }

    private function deleteSQL(string $table, array $conditions = []) : string
    {
        return "DELETE FROM ".$table.$this->where($conditions);
    }

    private function insertSQL(string $table, array $records) : string
    {
        $c = $this->db->columnQuoteChar;
        $first = reset($records);
        $columnsStr = $c.implode("$c, $c", array_keys($first)).$c;
        $line = sprintf("INSERT INTO %s (%s) VALUES ", $table, $columnsStr);
        $i = 0;
        foreach ($records as $record) {
            $i++;
            if ($i > 1) {
                $line .= ",";
            }
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

    public function updateBlob(string $table, string $column, string $blob, array $conditions = []) : int
    {
        $result = $this->db->updateBlob($table, $column, $blob, $this->where($conditions));
        return $this-db>affectedRows($result);
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
            $c = $this->db->columnQuoteChar;
            $escaped = $this->db->escape($newData);
            $keyEqualsVal = array_map([$this, 'columnKeyEqualsValue'], $escaped);
            $where = $this->where($conditions);
            $query = "UPDATE ".$table." SET ".implode(", ", $keyEqualsVal).$where;
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

}

?>
