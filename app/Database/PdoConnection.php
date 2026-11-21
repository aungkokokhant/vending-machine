<?php

namespace App\Database;

use Illuminate\Database\ConnectionInterface;
use PDO;
use PDOStatement;

class PdoConnection
{
    public function __construct(private ConnectionInterface $connection) {}

    public function pdo(): PDO
    {
        $pdo = $this->connection->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    public function execute(string $sql, array $bindings = []): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        foreach ($bindings as $key => $value) {
            $statement->bindValue(is_int($key) ? $key + 1 : ':'.$key, $value,
                is_int($value) ? PDO::PARAM_INT : ($value === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
        }
        $statement->execute();

        return $statement;
    }

    public function transaction(callable $callback): mixed
    {
        return $this->connection->transaction($callback, 3);
    }

    public function supportsRowLocks(): bool
    {
        return $this->connection->getDriverName() === 'mysql';
    }
}
