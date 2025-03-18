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
        $host = 'localhost';
        $dbname = 'tasks_db';
        $username = 'db_user';
        $password = 'db_password';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            return null;
        }
    }
}
