<?php
namespace App\Repository;

use PDO;

class ClientRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureRoleColumn();
    }

    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM clients')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        return $client ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE LOWER(email) = LOWER(:email)');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);

        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        return $client ?: null;
    }

    public function findByLogin(string $login): ?array
    {
        $login = trim($login);

        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE LOWER(email) = LOWER(:login) OR LOWER(full_name) = LOWER(:login) LIMIT 1');
        $stmt->execute(['login' => $login]);

        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        return $client ?: null;
    }

    public function insert(array $data): int
    {
        $sql = "
            INSERT INTO clients (
                full_name, email, phone, idnp,
                driver_license, driver_rating, password_hash, role
            )
            VALUES (
                :full_name, :email, :phone, :idnp,
                :driver_license, :driver_rating, :password_hash, :role
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'idnp' => $data['idnp'],
            'driver_license' => $data['driver_license'],
            'driver_rating' => $data['driver_rating'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'client',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateById(int $id, array $data): bool
    {
        $sql = "
            UPDATE clients SET
                full_name = :full_name,
                email = :email,
                phone = :phone,
                idnp = :idnp,
                driver_license = :driver_license,
                driver_rating = :driver_rating,
                password_hash = :password_hash,
                role = :role
            WHERE id = :id
        ";

        return $this->pdo->prepare($sql)->execute([
            'id' => $id,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'idnp' => $data['idnp'],
            'driver_license' => $data['driver_license'],
            'driver_rating' => $data['driver_rating'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'client',
        ]);
    }

    public function deleteById(int $id): bool
    {
        return $this->pdo
            ->prepare('DELETE FROM clients WHERE id = :id')
            ->execute(['id' => $id]);
    }

    private function ensureRoleColumn(): void
    {
        $this->pdo->exec("
            ALTER TABLE clients
            ADD COLUMN IF NOT EXISTS role VARCHAR(30) NOT NULL DEFAULT 'client'
        ");

        $this->pdo->exec("
            ALTER TABLE clients
            DROP CONSTRAINT IF EXISTS clients_role_check
        ");

        $this->pdo->exec("
            ALTER TABLE clients
            ADD CONSTRAINT clients_role_check
            CHECK (role IN ('client', 'admin'))
        ");
    }
}
