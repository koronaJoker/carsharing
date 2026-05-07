<?php
namespace App\Repository;

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;
use App\Core\MongoDatabase;

/**
 * Repository for managing GPS location tracking with TTL and Time Series.
 * Stores GPS coordinates with 1-month retention and 1-minute update frequency.
 */
class GeoLocationRepository
{
    private const COLLECTION = 'rental_gps_tracks';
    private const TTL_SECONDS = 2592000;

    private ?Client $mongo = null;
    private ?string $timeField = null;

    public function __construct()
    {
    }

    /**
     * Creates or updates the GPS collection with 1-month retention.
     */
    public function initializeCollection(): void
    {
        $db = $this->getMongo()->selectDatabase(MongoDatabase::getDatabaseName());

        try {
            $collectionInfo = $this->getCollectionInfo();

            if ($collectionInfo === null) {
                $db->createCollection(self::COLLECTION, [
                    'timeseries' => [
                        'timeField' => 'ts',
                        'metaField' => 'metadata',
                        'granularity' => 'minutes'
                    ],
                    'expireAfterSeconds' => self::TTL_SECONDS
                ]);
                $this->timeField = 'ts';
                return;
            }

            $this->timeField = $this->extractTimeField($collectionInfo);

            if ($this->timeField !== null) {
                $db->command([
                    'collMod' => self::COLLECTION,
                    'expireAfterSeconds' => self::TTL_SECONDS
                ]);
                return;
            }

            $this->timeField = 'ts';
            $db->selectCollection(self::COLLECTION)->createIndex(
                ['ts' => 1],
                ['expireAfterSeconds' => self::TTL_SECONDS]
            );
        } catch (\Exception $e) {
            if (MongoDatabase::isDebugEnabled()) {
                error_log("GeoLocationRepository: " . $e->getMessage());
            }
        }
    }

    /**
     * Saves GPS location update for an active rental.
     * Designed to be called every minute.
     *
     * @param int $rentalId Rental identifier
     * @param int $carId Car identifier
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @param float $speed Current speed in km/h (optional)
     * @param int $accuracy Accuracy in meters (optional)
     *
     * @return bool Success status
     */
    public function saveGpsLocation(
        int $rentalId,
        int $carId,
        float $latitude,
        float $longitude,
        float $speed = 0.0,
        int $accuracy = 10
    ): bool {
        try {
            $this->initializeCollection();

            $db = $this->getMongo()->selectDatabase(MongoDatabase::getDatabaseName());
            $collection = $db->selectCollection(self::COLLECTION);
            $now = new UTCDateTime();
            $timeField = $this->getTimeField();

            $document = [
                $timeField => $now,
                'ts' => $now,
                'metadata' => [
                    'rental_id' => $rentalId,
                    'car_id' => $carId
                ],
                'location' => [
                    'type' => 'Point',
                    'coordinates' => [$longitude, $latitude]  // GeoJSON: [lon, lat]
                ],
                'speed' => $speed,
                'accuracy' => $accuracy
            ];

            $result = $collection->insertOne($document);

            return $result->getInsertedId() !== null;
        } catch (\Exception $e) {
            if (MongoDatabase::isDebugEnabled()) {
                error_log("GeoLocationRepository::saveGpsLocation: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Gets the latest GPS location for a rental.
     *
     * @return array<string, mixed>|null Location document or null if not found
     */
    public function getLatestLocation(int $rentalId): ?array
    {
        try {
            $db = $this->getMongo()->selectDatabase(MongoDatabase::getDatabaseName());
            $collection = $db->selectCollection(self::COLLECTION);
            $timeField = $this->getTimeField();

            $location = $collection->findOne(
                ['metadata.rental_id' => $rentalId],
                ['sort' => [$timeField => -1]]
            );

            return $location ? $location->bsonSerialize() : null;
        } catch (\Exception $e) {
            if (MongoDatabase::isDebugEnabled()) {
                error_log("GeoLocationRepository::getLatestLocation: " . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * Gets the complete route for a rental during the last N minutes.
     *
     * @param int $rentalId Rental identifier
     * @param int $minutes Number of minutes to look back (default: 60)
     *
     * @return array<int, array<string, mixed>> Array of location documents
     */
    public function getRoute(int $rentalId, int $minutes = 60): array
    {
        try {
            $db = $this->getMongo()->selectDatabase(MongoDatabase::getDatabaseName());
            $collection = $db->selectCollection(self::COLLECTION);
            $timeField = $this->getTimeField();

            $startTime = new UTCDateTime((time() - ($minutes * 60)) * 1000);

            $locations = $collection->find(
                [
                    'metadata.rental_id' => $rentalId,
                    $timeField => ['$gte' => $startTime]
                ],
                ['sort' => [$timeField => 1]]
            );

            return iterator_to_array($locations);
        } catch (\Exception $e) {
            if (MongoDatabase::isDebugEnabled()) {
                error_log("GeoLocationRepository::getRoute: " . $e->getMessage());
            }
            return [];
        }
    }

    /**
     * Gets the latest location for all active rentals.
     *
     * @return array<int, array<string, mixed>> Array of latest locations grouped by rental
     */
    public function getAllLatestLocations(): array
    {
        try {
            $db = $this->getMongo()->selectDatabase(MongoDatabase::getDatabaseName());
            $collection = $db->selectCollection(self::COLLECTION);
            $timeField = $this->getTimeField();

            $results = $collection->aggregate([
                [
                    '$sort' => [$timeField => -1]
                ],
                [
                    '$group' => [
                        '_id' => '$metadata.rental_id',
                        'latest' => ['$first' => '$$ROOT']
                    ]
                ]
            ]);

            return iterator_to_array($results);
        } catch (\Exception $e) {
            if (MongoDatabase::isDebugEnabled()) {
                error_log("GeoLocationRepository::getAllLatestLocations: " . $e->getMessage());
            }
            return [];
        }
    }

    /**
     * Calculates the distance traveled for a rental during the last N minutes.
     * Uses Haversine formula for rough estimation.
     *
     * @param int $rentalId Rental identifier
     * @param int $minutes Number of minutes to look back (default: 60)
     *
     * @return float Distance in kilometers
     */
    public function calculateDistance(int $rentalId, int $minutes = 60): float
    {
        $route = $this->getRoute($rentalId, $minutes);

        if (count($route) < 2) {
            return 0.0;
        }

        $totalDistance = 0.0;

        foreach ($route as $i => $location) {
            if ($i === 0) {
                continue;
            }

            $prevLocation = $route[$i - 1];
            $coords1 = $prevLocation['location']['coordinates'] ?? [0, 0];
            $coords2 = $location['location']['coordinates'] ?? [0, 0];

            $totalDistance += $this->haversineDistance(
                $coords1[1], $coords1[0],  // prev lat, lon
                $coords2[1], $coords2[0]   // curr lat, lon
            );
        }

        return round($totalDistance, 2);
    }

    /**
     * Haversine formula to calculate distance between two coordinates.
     *
     * @param float $lat1 Latitude 1
     * @param float $lon1 Longitude 1
     * @param float $lat2 Latitude 2
     * @param float $lon2 Longitude 2
     *
     * @return float Distance in kilometers
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Deletes all GPS records for a completed rental.
     */
    public function deleteRentalLocation(int $rentalId): bool
    {
        try {
            $db = $this->getMongo()->selectDatabase(MongoDatabase::getDatabaseName());
            $collection = $db->selectCollection(self::COLLECTION);

            $result = $collection->deleteMany(['metadata.rental_id' => $rentalId]);

            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            if (MongoDatabase::isDebugEnabled()) {
                error_log("GeoLocationRepository::deleteRentalLocation: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Returns the active time field for the collection.
     */
    private function getTimeField(): string
    {
        if ($this->timeField === null) {
            $this->initializeCollection();
        }

        return $this->timeField ?? 'ts';
    }

    /**
     * Returns MongoDB collection metadata, if the collection already exists.
     *
     * @return array<string, mixed>|null
     */
    private function getCollectionInfo(): ?array
    {
        $db = $this->getMongo()->selectDatabase(MongoDatabase::getDatabaseName());
        $collections = $db->listCollections(['filter' => ['name' => self::COLLECTION]]);

        foreach ($collections as $collection) {
            return $collection->getInfo();
        }

        return null;
    }

    /**
     * Returns the shared MongoDB client.
     */
    private function getMongo(): Client
    {
        if ($this->mongo === null) {
            $this->mongo = MongoDatabase::getClient();
        }

        return $this->mongo;
    }

    /**
     * Reads the time-series time field from collection metadata.
     *
     * @param array<string, mixed> $collectionInfo
     */
    private function extractTimeField(array $collectionInfo): ?string
    {
        $options = $collectionInfo['options'] ?? [];
        $timeseries = $options['timeseries'] ?? $options['timeseriesFields'] ?? null;

        if (is_array($timeseries) && isset($timeseries['timeField'])) {
            return (string)$timeseries['timeField'];
        }

        return null;
    }
}
