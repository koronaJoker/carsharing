<?php

namespace App\Services;

use App\Models\Rental;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use MongoDB\Client;

class MongoTelemetryService
{
    public function captureActiveRentalsSnapshot(): void
    {
        $client = $this->buildClient();

        if (! $client) {
            return;
        }

        $databaseName = env('MONGODB_DATABASE', 'carsharing_nosql');
        $database = $client->selectDatabase($databaseName);

        $activeRentals = Rental::with(['car', 'user'])
            ->where('status', 'active')
            ->get();

        foreach ($activeRentals as $rental) {
            if (! $rental->car) {
                continue;
            }

            [$lat, $lng] = $this->nextLocation((int) $rental->car_id);
            $now = now()->toIso8601String();

            $database->selectCollection('car_current_locations')->updateOne(
                ['car_id' => (int) $rental->car_id],
                [
                    '$set' => [
                        'car_id' => (int) $rental->car_id,
                        'rental_id' => (int) $rental->id,
                        'user_id' => (int) $rental->user_id,
                        'status' => $rental->status,
                        'brand' => $rental->car->brand,
                        'plate_number' => (string) ($rental->car->number_plate ?? ''),
                        'location' => [
                            'type' => 'Point',
                            'coordinates' => [$lng, $lat],
                        ],
                        'updated_at' => $now,
                    ],
                ],
                ['upsert' => true]
            );

            $database->selectCollection('rental_gps_tracks')->insertOne([
                'car_id' => (int) $rental->car_id,
                'rental_id' => (int) $rental->id,
                'user_id' => (int) $rental->user_id,
                'status' => $rental->status,
                'latitude' => $lat,
                'longitude' => $lng,
                'location' => [
                    'type' => 'Point',
                    'coordinates' => [$lng, $lat],
                ],
                'recorded_at' => $now,
            ]);
        }
    }

    public function logEvent(string $type, string $message, array $payload = []): void
    {
        $client = $this->buildClient();

        if (! $client) {
            return;
        }

        $databaseName = env('MONGODB_DATABASE', 'carsharing_nosql');
        $database = $client->selectDatabase($databaseName);

        $database->selectCollection('car_logs')->insertOne(array_merge([
            'type' => $type,
            'message' => $message,
            'created_at' => now()->toIso8601String(),
        ], $payload));
    }

    private function buildClient(): ?Client
    {
        $uri = env('MONGODB_URI');

        if (! $uri) {
            Log::warning('MONGODB_URI is empty. Mongo telemetry skipped.');

            return null;
        }

        if (! class_exists(Client::class)) {
            Log::warning('mongodb/mongodb package is not available. Mongo telemetry skipped.');

            return null;
        }

        try {
            return new Client($uri);
        } catch (\Throwable $e) {
            Log::warning('MongoDB connection failed. Telemetry skipped.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function nextLocation(int $carId): array
    {
        $cacheKey = 'car_location_'.$carId;
        $last = Cache::get($cacheKey);

        if (is_array($last) && isset($last['lat'], $last['lng'])) {
            $lat = (float) $last['lat'];
            $lng = (float) $last['lng'];
        } else {
            $lat = 47.0105 + ($carId % 10) * 0.001;
            $lng = 28.8638 + ($carId % 10) * 0.001;
        }

        $lat += random_int(-2, 2) / 10000;
        $lng += random_int(-2, 2) / 10000;

        Cache::put($cacheKey, ['lat' => $lat, 'lng' => $lng], now()->addHours(3));

        return [$lat, $lng];
    }
}
