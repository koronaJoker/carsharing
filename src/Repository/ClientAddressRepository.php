<?php

namespace App\Repository;

use PDO;

/**
 * Provides database operations for client addresses.
 */
class ClientAddressRepository extends BaseRepository
{
    /**
     * Initializes the repository and ensures the addresses table exists.
     */
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    /**
     * Returns addresses for a client.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByClientId(int $clientId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM client_adresses
            WHERE client_id = :client_id
            ORDER BY created_at DESC, id DESC
        ');
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts a new address and returns its identifier.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO client_adresses (client_id, title, address, latitude, longitude)
            VALUES (:client_id, :title, :address, :latitude, :longitude)
        ');
        $stmt->execute([
            'client_id' => $data['client_id'],
            'title' => $data['title'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Updates an address owned by a client.
     *
     * @param array<string, mixed> $data
     */
    public function updateForClient(int $id, int $clientId, array $data): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE client_adresses
            SET title = :title,
                address = :address,
                latitude = :latitude,
                longitude = :longitude
            WHERE id = :id AND client_id = :client_id
        ');

        return $stmt->execute([
            'id' => $id,
            'client_id' => $clientId,
            'title' => $data['title'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ]);
    }

    /**
     * Deletes an address owned by a client.
     */
    public function deleteForClient(int $id, int $clientId): bool
    {
        return $this->pdo
            ->prepare('DELETE FROM client_adresses WHERE id = :id AND client_id = :client_id')
            ->execute(['id' => $id, 'client_id' => $clientId]);
    }

    /**
     * Creates the address table if it is missing.
     */
    private function ensureTable(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS client_adresses (
                id SERIAL PRIMARY KEY,
                client_id INT NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
                title VARCHAR(80) NOT NULL,
                address VARCHAR(255) NOT NULL,
                latitude NUMERIC(10,7) NOT NULL CHECK (latitude >= -90 AND latitude <= 90),
                longitude NUMERIC(10,7) NOT NULL CHECK (longitude >= -180 AND longitude <= 180),
                created_at TIMESTAMP DEFAULT NOW()
            )
        ');
    }
}
