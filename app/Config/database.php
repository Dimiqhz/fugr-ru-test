<?php
namespace App\Config;

use PDO;
use PDOException;

class Database
{
    /**
     * Метод для PDO соединения
     */
    public static function getConnection(): ?PDO
    {
        $host = 'mysql_tasks';
        $dbname = 'tasks_db';
        $username = 'user';
        $password = 'password';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET sql_mode=''");
            return $pdo;
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            return null;
        }
    }
}
