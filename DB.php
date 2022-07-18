<?php

namespace Krag;

class DB
{

    private \PDO $conn;
    public string $dbType;
    public string $columnQuoteChar;
    public string $randomFuncSQL;

    public function __construct(
        public string $dsn,
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
        case 'pgsql':
            $this->columnQuoteChar = '"';
            $this->randomFuncSQL = 'RANDOM()';
            break;
        case 'sqlsrv':
            $this->columnQuoteChar = '"';
            $this->randomFuncSQL = 'RAND()';
            break;
        case 'mysql':
        default:
            $this->columnQuoteChar = '`';
            $this->randomFuncSQL = 'RAND()';
            break;
        }
    }

    public function begin()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollBack()
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

    public function updateBlob(string $table, string $column, string $blob, string $strCondtiions = '') : object
    {
        $c = $this->columnQuoteChar;
        $statement = $this->conn->prepare("UPDATE ".$table." SET ".$c.$column.$c." = ? ".$strCondtiions);
        $statement->bindParam(1, $blob, \PDO::PARAM_LOB);
        $this->begin();
        $statement->execute();
        $this->commit();
        return $statement;
    }

    public function close()
    {
        $this->conn->closeCursor();
    }

    public function insertId() : int
    {
        $this->conn->lastInsertId();
    }

    public function affectedRows($result) : int
    {
        return $result->rowCount();
    }

    public function escape(string|array $toEscape) : string
    {
        if (is_array($toEscape))
        {
            return array_map([$this, 'escape'], $toEscape);
        }
        return substr($this->conn->quote($str), 1, -1);
    }

    public function columnEscape(string|array $toEscape) : string
    {
        if (is_array($toEscape))
        {
            return array_map([$this, 'columnEscape'], $toEscape);
        }
        return str_replace(['`', '"', "'", '|'], '', $toEscape);
    }

    public function tableEscape(string $toEscape) : string
    {
        return str_replace(['`', '"', "'", '|'], '', $toEscape);
    }

}

?>
