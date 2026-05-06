<?php

namespace App\Repository;

use PDO;

class AdminRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE login = :login');
        $stmt->execute(['login' => trim($login)]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $admin ?: null;
    }

    private function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id SERIAL PRIMARY KEY,
                login VARCHAR(80) UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role VARCHAR(30) NOT NULL DEFAULT 'admin'
                    CHECK (role IN ('admin')),
                created_at TIMESTAMP DEFAULT NOW()
            )
        ");

        $stmt = $this->pdo->prepare("
            INSERT INTO admins (login, password_hash, role)
            VALUES (:login, :password_hash, 'admin')
            ON CONFLICT (login) DO NOTHING
        ");

        $stmt->execute([
            'login' => 'superadmin_2026',
            'password_hash' => '$2y$10$QL1bz0RxgDyrpDzb3FBxvO11pts1zv16GHEyUP5hL1BNcMA5zO.E6',
        ]);
    }
}
