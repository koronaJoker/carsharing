<?php

namespace App\Repository;

use PDO;

class FineRepository extends BaseRepository
{
    public function getAll(): array
    {
        $sql = "
            SELECT 
                fines.*,
                clients.full_name AS client_name
            FROM fines
            JOIN clients ON fines.client_id = clients.id
            ORDER BY fines.id DESC
        ";

        return $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fines WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $fine = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fine ?: null;
    }

    public function insert(array $data): bool
    {
        $sql = "
            INSERT INTO fines 
                (client_id, rental_id, title, description, amount, rating_penalty, status)
            VALUES 
                (:client_id, :rental_id, :title, :description, :amount, :rating_penalty, :status)
        ";

        return $this->pdo->prepare($sql)->execute([
            'client_id' => $data['client_id'],
            'rental_id' => $data['rental_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'rating_penalty' => $data['rating_penalty'] ?? 0.1,
            'status' => $data['status'] ?? 'unpaid',
        ]);
    }

    public function updateById(int $id, array $data): bool
    {
        $sql = "
            UPDATE fines SET
                client_id = :client_id,
                rental_id = :rental_id,
                title = :title,
                description = :description,
                amount = :amount,
                rating_penalty = :rating_penalty,
                status = :status
            WHERE id = :id
        ";

        return $this->pdo->prepare($sql)->execute([
            'id' => $id,
            'client_id' => $data['client_id'],
            'rental_id' => $data['rental_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'rating_penalty' => $data['rating_penalty'] ?? 0.1,
            'status' => $data['status'] ?? 'unpaid',
        ]);
    }

    public function deleteById(int $id): bool
    {
        return $this->pdo
            ->prepare('DELETE FROM fines WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
