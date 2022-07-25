<?php

namespace Krag;

class DB implements DBInterface
{
    private \PDO $conn;
    private string $columnQuoteCharLeft;
    private string $columnQuoteCharRight;
    public string $randomFuncSQL;

    public function __construct(
        string $type,
        string $database,
        string $host = '',
        string $username = '',
        string $password = '',
        private ?LogInterface $log = null,
    ) {
        $dsn = $this->makeDSN($type, $host, $database);
        $this->conn = new \PDO($dsn, $username, $password);
        $this->setDatabaseParameters($type);
    }

    protected function makeDSN(string $type, string $host, string $database): string
    {
        $ret = $type.':';
        $ret .= ($host) ? 'host='.$host : '';
        $ret .= ($database) ? 'dbname='.$database : '';
        return $ret;
    }

    protected function setDatabaseParameters(string $type): void
    {
        switch ($type) {
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

    public function begin(): bool
    {
        return $this->conn->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->conn->commit();
    }

    public function rollBack(): bool
    {
        return $this->conn->rollBack();
    }

    private function logAnyError(\PDO|\PDOStatement $obj): void
    {
        if ($obj->errorCode() != '00000') {
            if (is_object($this->log)) {
                [$sqlState, $driverCode, $message] = $obj->errorInfo();
                $this->log->error($message, compact('sqlState', 'driverCode'));
            }
        }
    }

    public function query(string $query): object
    {
        if (is_object($this->log)) {
            $this->log->debug($query);
        }
        $result = $this->conn->query($query);
        if (is_object($result)) {
            $this->logAnyError($result);
        } else {
            $this->logAnyError($this->conn);
        }
        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAssoc(object $result): array
    {
        return $result->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, mixed>
     */
    public function fetchRow(object $result): array
    {
        return $result->fetch(\PDO::FETCH_NUM);
    }

    public function closeCursor(object $result): bool
    {
        return $result->closeCursor();
    }

    public function insertId(): int
    {
        return intval($this->conn->lastInsertId());
    }

    public function affectedRows(object $result): int
    {
        return $result->rowCount();
    }

    /**
     * @param string|array<mixed> $toEscape
     * @return string|array<mixed>
     */
    public function escape(string|array $toEscape): string|array
    {
        if (is_array($toEscape)) {
            return array_map($this->escape(...), $toEscape);
        }
        return substr($this->conn->quote($toEscape), 1, -1);
    }

    private function noSpecials(string $toEscape): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', $toEscape);
    }

    public function tableEscape(string $toEscape): string
    {
        $cl = $this->columnQuoteCharLeft;
        $cr = $this->columnQuoteCharRight;
        return $cl.$this->noSpecials($toEscape).$cr;
    }

    /**
     * @param string|array<mixed> $toEscape
     */
    public function columnEscape(string|array $toEscape, ?string $table = null): string
    {
        $cl = $this->columnQuoteCharLeft;
        $cr = $this->columnQuoteCharRight;
        if (is_array($toEscape)) {
            return implode(', ', array_map($this->columnEscape(...), $toEscape));
        }
        $table = (is_string($table)) ? $this->tableEscape($table).'.' : '';
        $escaped = $this->noSpecials($toEscape);
        return $cl.$table.$escaped.$cr;
    }

    public function aliasEscape(string $toEscape): string
    {
        return $this->noSpecials($toEscape);
    }

    public function setBlob(string $query, string $blob): object
    {
        $statement = $this->conn->prepare($query);
        $statement->bindParam(1, $blob, \PDO::PARAM_LOB);
        $this->begin();
        $statement->execute();
        $this->commit();
        return $statement;
    }
}
