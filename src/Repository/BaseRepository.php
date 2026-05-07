<?php

namespace App\Repository;

use PDO;
use App\Core\Database;

/**
 * Base class for repositories that need a PDO connection.
 */
abstract class BaseRepository
{
    /**
     * Shared database connection.
     */
    protected PDO $pdo;

    /**
     * Initializes the repository connection.
     */
    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }
}
