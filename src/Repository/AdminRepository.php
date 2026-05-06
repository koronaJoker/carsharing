<?php

namespace App\Repository;

use PDO;

class AdminRepository extends BaseRepository
{
    public function findByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE login = :login');
        $stmt->execute(['login' => trim($login)]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $admin ?: null;
    }
}
