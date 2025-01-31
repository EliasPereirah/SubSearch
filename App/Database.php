<?php

namespace App;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
class Database {
    private PDO $PDO;


    /**
     * @param string $dbname optional database name if already defined in config
     * @throws Exception
     */
    public function __construct(string $dbname = '') {
        if (!$dbname) {
            $dbname = DB_CONFIG['dbname'];
        }
        try {
            $dns = DB_CONFIG['driver'] . ':host=' . DB_CONFIG['host'] . ';dbname=' . $dbname . ';port=' . DB_CONFIG['port'];
            $this->PDO = new PDO($dns, DB_CONFIG['username'], DB_CONFIG['passwd'], DB_CONFIG['options']);
        } catch (PDOException $exception) {
            $msg = PRODUCTION ? $exception->getCode() : $exception->getMessage();
            $msg = "Ops, falha ao tentar fazer conexão ao banco de dados: $msg";
            throw new Exception($msg);
        }
    }


    /* @param array $binds binds para os bind values
     * @param string $sql SQL query statement
     * @return PDOStatement
     */
    public function select(string $sql, array $binds): PDOStatement {
        $stmt = $this->PDO->prepare($sql);
        $stmt->execute($binds);
        return $stmt;
    }

    public function insert(string $sql, array $binds): bool {
        return $this->executeStatement($sql, $binds);
    }


    public function delete(string $sql, array $binds): bool {
        return $this->executeStatement($sql, $binds);
    }


    public function update(string $sql, array $binds): bool {
        return $this->executeStatement($sql, $binds);
    }


    private function executeStatement(string $sql, array $binds): bool {
        $stmt = $this->PDO->prepare($sql);
        $stmt->execute($binds);
        return $stmt->rowCount() > 0;
    }
}
