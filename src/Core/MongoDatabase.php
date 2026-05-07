<?php

namespace App\Core;

use Dotenv\Dotenv;
use MongoDB\Client;
use RuntimeException;

/**
 * Provides a MongoDB connection for NoSQL document storage.
 */
class MongoDatabase
{
    /**
     * Fallback Atlas connection string used when data.env is not loaded.
     */
    private const DEFAULT_URI = 'mongodb+srv://kirovk899_db_user:ldh8rUPYFzk9Yyal@cluster0.5n8uruq.mongodb.net/?retryWrites=true&w=majority';

    /**
     * Fallback MongoDB database name.
     */
    private const DEFAULT_DATABASE = 'carsharing_nosql';

    /**
     * Cached MongoDB client instance.
     */
    private static ?Client $client = null;

    /**
     * Returns whether the MongoDB PHP extension is available.
     */
    public static function isAvailable(): bool
    {
        return class_exists(Client::class);
    }

    /**
     * Returns the shared MongoDB client.
     */
    public static function getClient(): Client
    {
        if (!self::isAvailable()) {
            throw new RuntimeException('MongoDB PHP library or extension is not installed.');
        }

        if (self::$client === null) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../', 'data.env');
            $dotenv->safeLoad();

            self::$client = new Client($_ENV['MONGODB_URI'] ?? self::DEFAULT_URI);
        }

        return self::$client;
    }

    /**
     * Returns the configured MongoDB database name.
     */
    public static function getDatabaseName(): string
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../', 'data.env');
        $dotenv->safeLoad();

        return $_ENV['MONGODB_DATABASE'] ?? self::DEFAULT_DATABASE;
    }

    /**
     * Returns whether MongoDB errors should be shown in the interface.
     */
    public static function isDebugEnabled(): bool
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../', 'data.env');
        $dotenv->safeLoad();

        return ($_ENV['MONGO_DEBUG'] ?? 'false') === 'true';
    }
}
