<?php
namespace App\Repository;

use PDO;

class CarRepository extends BaseRepository
{
    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM cars')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cars WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $car = $stmt->fetch(PDO::FETCH_ASSOC);

        return $car ?: null;
    }

    public function getAvailable(): array
    {
        return $this->pdo
            ->query("SELECT * FROM cars WHERE status = 'available' ORDER BY brand, model")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->pdo
            ->prepare('UPDATE cars SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status]);
    }

    public function insert(array $data): int
    {
        $sql = "
            INSERT INTO cars (
                brand, model, year, plate_number,
                fuel_type, transmission, price_per_minute, status
            )
            VALUES (
                :brand, :model, :year, :plate_number,
                :fuel_type, :transmission, :price_per_minute, :status
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateById(int $id, array $data): bool
    {
        $sql = "
            UPDATE cars SET
                brand = :brand,
                model = :model,
                year = :year,
                plate_number = :plate_number,
                fuel_type = :fuel_type,
                transmission = :transmission,
                price_per_minute = :price_per_minute,
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
            ->prepare('DELETE FROM cars WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
