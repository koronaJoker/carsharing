<?php
namespace App\Repository;

use PDO;

class PaymentRepository extends BaseRepository
{
    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM payments')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $payment ?: null;
    }

    public function findByRentalId(int $rentalId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE rental_id = :rental_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['rental_id' => $rentalId]);

        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $payment ?: null;
    }

    public function updateById(int $id, array $data): bool
    {
        $sql = "
            UPDATE payments SET
                rental_id = :rental_id,
                amount = :amount,
                payment_method = :payment_method,
                payment_status = :payment_status,
                paid_at = :paid_at
            WHERE id = :id
        ";

        return $this->pdo->prepare($sql)->execute([
            'id' => $id,
            ...$data
        ]);
    }

    public function insert(array $data): int
    {
        $sql = "
            INSERT INTO payments (
                rental_id, amount, payment_method,
                payment_status, paid_at
            )
            VALUES (
                :rental_id, :amount, :payment_method,
                :payment_status, :paid_at
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    public function deleteById(int $id): bool
    {
        return $this->pdo
            ->prepare('DELETE FROM payments WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
