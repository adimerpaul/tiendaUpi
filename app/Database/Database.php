<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        try {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $dbName = getenv('DB_NAME') ?: 'tienda';
            $safeDbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName) ?: 'tienda';

            $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $serverPdo = new PDO($serverDsn, $user, $pass);
            $serverPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $dbDsn = "mysql:host={$host};port={$port};dbname={$safeDbName};charset=utf8mb4";
            self::$connection = new PDO($dbDsn, $user, $pass);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::migrate(self::$connection);
            return self::$connection;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(
                ['error' => 'No se pudo conectar a MySQL. Revisa DB_HOST, DB_PORT, DB_USER, DB_PASS y DB_NAME.'],
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                precio DECIMAL(10,2) NOT NULL,
                cantidad INTEGER NOT NULL,
                marca VARCHAR(120) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}
