<?php

namespace Krag;

class DB
{
    private \PDO $conn;
    public string $dbType;
    public $columnQuoteChar;
    public $columnQuoteFunc;
    public $randomFuncSQL;

    public function __construct(
        public string $dsn,
        private string $userName,
        private string $password,
        public $logQueryHandler = false,
        public $logErrorHandler = false,
        public $connectionFailHandler = false
    )
    {
        try
        {
            $this->conn = new \PDO($dsn, $userName, $password);
        }
        catch (\PDOException $e) {
            if ($connectionFailHandler)
            {
                call_user_func_array($failHandler, [$e]);
            }
        }
        preg_match('/(.*):/', $dsn, $matches);
        if ($matches)
        {
            $this->dbType = $matches[1];
        }
        switch($this->dbType)
        {
        case 'pgsql':
            $this->columnQuoteChar = '"';
            $this->columnQuoteFunc = [$this, 'keyEqualsValue'];
            $this->randomFuncSQL = 'RANDOM()';
            break;
        case 'sqlsrv':
            $this->columnQuoteChar = '"';
            $this->columnQuoteFunc = [$this, 'keyEqualsValue'];
            $this->randomFuncSQL = 'RAND()';
            break;
        case 'mysql':
        default:
            $this->columnQuoteChar = '`';
            $this->columnQuoteFunc = [$this, 'backtickKeyEqualsValue'];
            $this->randomFuncSQL = 'RAND()';
            break;
        }
    }

    public function keyEqualsValue(mixed $item, string $key, string $table = '')
    {
        if ($table)
        {
            return $table.".".$key."='".$item."'";
        }
        return $key."='".$item."'";
    }

    public function backtickKeyEqualsValue(mixed $item, string $key, string $table = '')
    {
        if ($table)
        {
            return "`".$table."`.`".$key."`='".$item."'";
        }
        return "`".$key."`='".$item."'";

    }

    public function quoteColumns(array $arr)
    {
        return array_map($this->columnQuoteFunc, $arr);
    }

    public function begin()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollback()
    {
        return $this->conn->rollBack();
    }

    private function logAnyError(\PDO|\PDOStatement $obj)
    {
        if ($obj->errorCode() != '00000')
        {
            if ($this->logErrorHandler)
            {
                [$sqlState, $driverCode, $driverMessage] = $obj->errorInfo();
                $logData = compact('sqlState', 'driverCode', 'driverMessage');
                call_user_func_array($this->logErrorHandler, [$driverMessage, $logData]);
            }
        }

    }

    public function query(string $query)
    {
        if ($this->logQueryHandler)
        {
            call_user_func_array($this->logQueryHandler, $query);
        }
        $result = $this->conn->query($query);
        if (is_object($return))
        {
            $this->logAnyError($return);
        }
        else
        {
            $this->logAnyError($db);
        }
        return $result;
    }

    public function fetchAssoc(object $result)
    {
        return $result->fetch(\PDO::FETCH_ASSOC);
    }

    public function fetchRow(object $result)
    {
        return $result->fetch(\PDO::FETCH_NUM);
    }

    public function updateBlob(string $table, string $column, string $blob, string $strCondtiions = '')
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

    public function insertId()
    {
        $this->conn->lastInsertId();
    }

    public function affectedRows($result)
    {
        return $result->rowCount();
    }

    public function escape(string $str)
    {
        return substr($this->conn->quote($str), 1, -1);
    }

    public function arrayEscape(array $arr)
    {
        return array_map([$this, 'escape'], $arr);
    }

}

?>
