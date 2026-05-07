<?php

namespace App\Core;
use PDO;
use Dotenv\Dotenv;

/**
 * Provides a shared PDO connection configured from environment variables.
 */
class Database
{
    /**
     * Cached PDO connection instance.
     */
    private static ?PDO $connection = null;

    /**
     * Returns the application database connection.
     */
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

            $timezone = $_ENV['APP_TIMEZONE'] ?? 'Europe/Bucharest';
            self::$connection->exec("SET TIME ZONE " . self::$connection->quote($timezone));
        }

        return self::$connection;
    }
}
