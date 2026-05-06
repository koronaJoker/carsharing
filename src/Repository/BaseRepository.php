<?php

namespace App\Repository;

use PDO;
use App\Core\Database;

abstract class BaseRepository
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }
}