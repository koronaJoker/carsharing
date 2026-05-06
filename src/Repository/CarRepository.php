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

    public function getAvailableFiltered(array $filters): array
    {
        $where = ["status = 'available'"];
        $params = [];

        if (($filters['brand'] ?? '') !== '') {
            $where[] = 'brand = :brand';
            $params['brand'] = $filters['brand'];
        }

        if (($filters['fuel_type'] ?? '') !== '') {
            $where[] = 'fuel_type = :fuel_type';
            $params['fuel_type'] = $filters['fuel_type'];
        }

        if (($filters['transmission'] ?? '') !== '') {
            $where[] = 'transmission = :transmission';
            $params['transmission'] = $filters['transmission'];
        }

        if (($filters['year'] ?? '') !== '') {
            $where[] = 'year = :year';
            $params['year'] = (int)$filters['year'];
        }

        if (($filters['max_price'] ?? '') !== '') {
            $where[] = 'price_per_minute <= :max_price';
            $params['max_price'] = (float)$filters['max_price'];
        }

        $sql = 'SELECT * FROM cars WHERE ' . implode(' AND ', $where) . ' ORDER BY brand, model';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilterOptions(): array
    {
        return [
            'brands' => $this->distinctAvailable('brand'),
            'fuelTypes' => $this->distinctAvailable('fuel_type'),
            'transmissions' => $this->distinctAvailable('transmission'),
            'years' => $this->distinctAvailable('year', 'DESC'),
            'maxPrice' => (float)$this->pdo
                ->query("SELECT COALESCE(MAX(price_per_minute), 0) FROM cars WHERE status = 'available'")
                ->fetchColumn(),
        ];
    }

    private function distinctAvailable(string $column, string $direction = 'ASC'): array
    {
        $allowedColumns = ['brand', 'fuel_type', 'transmission', 'year'];
        $direction = $direction === 'DESC' ? 'DESC' : 'ASC';

        if (!in_array($column, $allowedColumns, true)) {
            return [];
        }

        return $this->pdo
            ->query("SELECT DISTINCT $column FROM cars WHERE status = 'available' AND $column IS NOT NULL ORDER BY $column $direction")
            ->fetchAll(PDO::FETCH_COLUMN);
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
