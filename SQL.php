<?php

namespace Krag;

class SQL
{
    public string $columnQuoteCharLeft;
    public string $columnQuoteCharRight;

    public function __construct(private DB $db) {
        $this->columnQuoteCharLeft = $db->columnQuoteCharLeft;
        $this->columnQuoteCharRight = $db->columnQuoteCharRight;
    }

    public function escape(string|array $toEscape) : SanitizedString
    {
        return $this->db->escape($toEscape);
    }

    public function columnEscape(string|array $toEscape) : SanitizedString
    {
        return $this->db->columnEscape($toEscape);
    }

    private function tableEscape(string $toEscape) : SanitizedString
    {
        return $this->db->tableEscape($toEscape);
    }

    public function tableQuoteAndEscape(string $table) : SanitizedString
    {
        $cl = $this->columnQuoteCharLeft;
        $cr = $this->columnQuoteCharRight;
        $table = $this->tableEscape($table);
        return SanitizedString($cl.$table.$cr);
    }

    public function columnsList(array $columns)
    {
        $cl = $this->columnQuoteCharLeft;
        $cr = $this->columnQuoteCharRight;
        $columns = $this->columnEscape($columns);
        return SanitizedString($cl.implode("$cr, $cl", $columns).$cr);
    }

    public function fieldEqualsValue(string $key, $value, ?string $table = null) : SanitizedString
    {
        $cl = $this->columnQuoteCharLeft;
        $cr = $this->columnQuoteCharRight;
        $key = $this->columnEscape($key);
        $value = $this->escape($value);
        $result = $cl.$key.$cr."='".$value."'";
        if ($table)
        {
            $result = $this->tableQuoteAndEscape($table).'.'.$result;
        }
        return SanitizedString($result);
    }

    public function multipleFieldsEqualValues(array $conditions, ?string $table = null) : SanitizedString
    {
        $ret = '';
        foreach ($conditions as $k => $v)
        {
            $ret .= $this->fieldEqualsValue($k, $v, $table);
        }
        return SanitizedString($ret);
    }

    public function where(array $conditions = [], ?string $table = null, bool $additional = false) : SanitizedString
    {
        $where = ($additional) ? ' ' : ' WHERE (1=1) ';
        if (count($conditions))
        {
            $keyEqualsVal = $this->multipleFieldsEqualValues($conditions, $table);
            $where .= " AND (".implode(") AND (", $keyEqualsVal).") ";
        }
        return SanitizedString($where);
    }

    public function group(string|array $groupBy) : SanitizedString
    {
        $cols = (is_array($groupBy)) ? $groupBy : array($groupBy);
        return SanitizedString("GROUP BY ".$this->columnsList($cols));
    }

    private function orderPart(string $sort, ?string $maybeDesc = null) : SanitizedString
    {
        $sort = $this->columnEscape($sort);
        $cl = $this->columnQuoteCharLeft;
        $cr = $this->columnQuoteCharRight;
        $ret = $cl.$sort.$cr." ";
        if ($maybeDesc)
        {
            $ret .= "DESC ";
        }
        return SanitizedString($ret);
    }

    public function order(string $sort, ?string $maybeDesc = null, ...$more) : SanitizedString
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
        return SanitizedString($ret);
    }

    public function limit(int $per_page, int $page = 1) : SanitizedString
    {
        $start = strval($per_page * ($page - 1));
        $count = strval($per_page);
        return SanitizedString("LIMIT $start, $count ");
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

    private function deleteSQL(string $table, array $conditions = []) : SanitizedString
    {
        $table = $this->tableQuoteAndEscape($table);
        return SanitizedString("DELETE FROM ".$table.$this->where($conditions));
    }

    private function insertSQL(string $table, array $records) : SanitizedString
    {
        $table = $this->tableQuoteAndEscape($table);
        $columns = array_keys(reset($records));
        $columnsStr = $this->columnsList($columns);
        $line = sprintf('INSERT INTO %s (%s) VALUES ', $table, $columnsStr);
        $i = 0;
        foreach ($records as $record) {
            $i++;
            if ($i > 1) {
                $line .= ",";
            }
            $line .= "('".implode("', '", $this->escape($record))."')";
        }
        return SanitizedString($line);
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
            $table = $this->tableQuoteAndEscape($table);
            $escaped = $this->escape($newData);
            $keyEqualsVal = $this->multipleFieldsEqualValues($conditions);
            $where = $this->where($conditions);
            $query = 'UPDATE '.$table.' SET '.implode(", ", $keyEqualsVal).$where;
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
