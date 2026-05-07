<?php
namespace App\Repository;

use PDO;

/**
 * Provides database operations for cars.
 */
class CarRepository extends BaseRepository
{
    /**
     * Returns all cars.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM cars')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a car by identifier.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cars WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $car = $stmt->fetch(PDO::FETCH_ASSOC);

        return $car ?: null;
    }

    /**
     * Returns all available cars ordered for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailable(): array
    {
        return $this->pdo
            ->query("SELECT * FROM cars WHERE status = 'available' ORDER BY brand, model")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns available cars filtered by the provided criteria.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
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

        $sortOptions = [
            'price_asc' => 'price_per_minute ASC, brand ASC, model ASC',
            'price_desc' => 'price_per_minute DESC, brand ASC, model ASC',
            'year_desc' => 'year DESC, brand ASC, model ASC',
            'year_asc' => 'year ASC, brand ASC, model ASC',
        ];
        $orderBy = $sortOptions[$filters['sort'] ?? ''] ?? 'brand ASC, model ASC';

        $sql = 'SELECT * FROM cars WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $orderBy;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns distinct filter options for available cars.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Returns distinct non-null values for an allowed car column.
     *
     * @return array<int, mixed>
     */
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

    /**
     * Updates the status of a car.
     */
    public function updateStatus(int $id, string $status): bool
    {
        return $this->pdo
            ->prepare('UPDATE cars SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status]);
    }

    /**
     * Inserts a new car and returns its identifier.
     *
     * @param array<string, mixed> $data
     */
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

    /**
     * Updates a car by identifier.
     *
     * @param array<string, mixed> $data
     */
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

    /**
     * Deletes a car by identifier.
     */
    public function deleteById(int $id): bool
    {
        return $this->pdo
            ->prepare('DELETE FROM cars WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
