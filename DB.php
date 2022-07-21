<?php

namespace Krag;

class DB implements DBInterface
{

    private \PDO $conn;
    private string $dbType;
    private string $columnQuoteCharLeft;
    private string $columnQuoteCharRight;
    private string $randomFuncSQL;

    public function __construct(
        private string $dsn,
        private string $userName,
        private string $password,
        ?Log $log = null,
    ) {
        $this->conn = new \PDO($dsn, $userName, $password);
        preg_match('/(.*):/', $dsn, $matches);
        if ($matches)
        {
            $this->dbType = $matches[1];
        }
        switch($this->dbType)
        {
        case 'mysql':
            $this->columnQuoteCharLeft = '`';
            $this->columnQuoteCharRight = '`';
            $this->randomFuncSQL = 'RAND()';
            break;
        case 'sqlsrv':
            $this->columnQuoteCharLeft = '[';
            $this->columnQuoteCharRight = ']';
            $this->randomFuncSQL = 'RAND()';
            break;
        case 'pgsql':
        default:
            $this->columnQuoteCharLeft = '"';
            $this->columnQuoteCharRight = '"';
            $this->randomFuncSQL = 'RANDOM()';
            break;
        }
    }

    public function begin() : bool
    {
        return $this->conn->beginTransaction();
    }

    public function commit() : bool
    {
        return $this->conn->commit();
    }

    public function rollBack() : bool
    {
        return $this->conn->rollBack();
    }

    private function logAnyError(\PDO|\PDOStatement $obj)
    {
        if ($obj->errorCode() != '00000')
        {
            if (is_object($this->log))
            {
                [$sqlState, $driverCode, $message] = $obj->errorInfo();
                $this->log->error($message, compact('sqlState', 'driverCode'));
            }
        }
    }

    public function query(string $query) : object
    {
        if (is_object($this->log))
        {
            $this->log->debug($query);
        }
        $result = $this->conn->query($query);
        if (is_object($result))
        {
            $this->logAnyError($result);
        }
        else
        {
            $this->logAnyError($db);
        }
        return $result;
    }

    public function fetchAssoc(object $result) : array
    {
        return $result->fetch(\PDO::FETCH_ASSOC);
    }

    public function fetchRow(object $result) : array
    {
        return $result->fetch(\PDO::FETCH_NUM);
    }

    public function closeCursor(object $result) : bool
    {
        return $object->closeCursor();
    }

    public function insertId() : int
    {
        $this->conn->lastInsertId();
    }

    public function affectedRows(object $result) : int
    {
        return $result->rowCount();
    }

    public function escape(string|array $toEscape) : string|array
    {
        if (is_array($toEscape))
        {
            return array_map($this->escape(...), $toEscape);
        }
        return substr($this->conn->quote($str), 1, -1);
    }

    private function noSpecials(string $toEscape) : string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', $str);
    }

    public function tableEscape(string $toEscape) : string
    {
        $cl = $this->columnQuoteCharLeft;
        $cr = $this->columnQuoteCharRight;
        return $cl.$this->noSpecials($toEscape).$cr;
    }

    public function columnEscape(string|array $toEscape, ?string $table = null) : string
    {
        $cl = $this->columnQuoteCharLeft;
        $cr = $this->columnQuoteCharRight;
        if (is_array($toEscape))
        {
            return implode(', ', array_map($this->columnEscape(...), $toEscape));
        }
        $table = (is_string($table)) ? $this->tableEscape($table).'.' : '';
        $escaped = $this->nosSpecials($toEscape);
        return $cl.$table.$escaped.$cr;
    }

    private function aliasEscape(string $str) : string
    {
        return $this->noSpecials($toEscape);
    }

    public function setBlob(string $query, string $blob) : object
    {
        $statement = $this->conn->prepare($query);
        $statement->bindParam(1, $blob, \PDO::PARAM_LOB);
        $this->begin();
        $statement->execute();
        $this->commit();
        return $statement;
    }

}

?>
