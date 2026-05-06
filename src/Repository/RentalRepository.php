<?php namespace App\Repository;

use PDO;

class RentalRepository extends BaseRepository
{
    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM rentals')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rentals WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $rental = $stmt->fetch(PDO::FETCH_ASSOC);

        return $rental ?: null;
    }

    public function findActiveByClientId(int $clientId): ?array
    {
        $sql = "
            SELECT rentals.*, cars.brand, cars.model, cars.year, cars.plate_number,
                   cars.fuel_type, cars.transmission, cars.price_per_minute
            FROM rentals
            JOIN cars ON cars.id = rentals.car_id
            WHERE rentals.client_id = :client_id AND rentals.status = 'active'
            ORDER BY rentals.start_time DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);

        $rental = $stmt->fetch(PDO::FETCH_ASSOC);

        return $rental ?: null;
    }

    public function finishActiveForClient(int $clientId): bool
    {
        $sql = "
            UPDATE rentals
            SET status = 'finished', end_time = NOW()
            WHERE client_id = :client_id AND status = 'active'
        ";

        return $this->pdo->prepare($sql)->execute(['client_id' => $clientId]);
    }

    public function insert(array $data): int
    {
        $sql = "
            INSERT INTO rentals (
                client_id, car_id, start_time,
                end_time, total_cost, status
            )
            VALUES (
                :client_id, :car_id, :start_time,
                :end_time, :total_cost, :status
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateById(int $id, array $data): bool
    {
        $sql = "
            UPDATE rentals SET
                client_id = :client_id,
                car_id = :car_id,
                start_time = :start_time,
                end_time = :end_time,
                total_cost = :total_cost,
                status = :status
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
            ->prepare('DELETE FROM rentals WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
