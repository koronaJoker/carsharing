<?php

namespace App\Core;
use PDO;
use Dotenv\Dotenv;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO 
    {
        if (self::$connection === null) {

            $dotenv = Dotenv::createImmutable(__DIR__ . "/../", "data.env");
            $dotenv->load();
            
            $host = $_ENV['DB_HOST'];
            $port = $_ENV['DB_PORT'];
            $db = $_ENV['DB_NAME'];
            $user = $_ENV['DB_USER'];
            $password = $_ENV['DB_PASSWORD'];

            $dsn = "pgsql:host=$host;port=$port;dbname=$db";

            self::$connection = new PDO($dsn, $user, $password);

            self::$connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );
        }

        return self::$connection;
    }
}