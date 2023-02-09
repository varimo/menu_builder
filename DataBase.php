<?php

namespace MDB;

class DataBase {
    private $pdo;

    public function __construct() {
        $dbSettings = (require __DIR__ . '/settings.php')['db'];
        $this->pdo = new \PDO('mysql:host=' . $dbSettings['host'] . ';dbname=' . $dbSettings['dbname'], $dbSettings['user'], $dbSettings['password']);
    }

    public function query(string $sql, $params = []) {
        $sth = $this->pdo->prepare($sql);
        $result = $sth->execute($params);
        if ($result === false) {
            return null;
        }
        return $sth->fetchAll(\PDO::FETCH_CLASS);
    }
}