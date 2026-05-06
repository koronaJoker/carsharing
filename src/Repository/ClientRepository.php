<?php
namespace App\Repository;

use PDO;

class ClientRepository extends BaseRepository
{
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
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE email = :email');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);

        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        return $client ?: null;
    }

    public function insert(array $data): int
    {
        $sql = "
            INSERT INTO clients (
                full_name, email, phone, idnp,
                driver_license, driver_rating, password_hash
            )
            VALUES (
                :full_name, :email, :phone, :idnp,
                :driver_license, :driver_rating, :password_hash
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

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
                password_hash = :password_hash
            WHERE id = :id
        ";

        return $this->pdo->prepare($sql)->execute([
            'id' => $id,
            ...$data
        ]);
    }

    public function deleteById(int $id): bool
    {
        return $this->pdo
            ->prepare('DELETE FROM clients WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
