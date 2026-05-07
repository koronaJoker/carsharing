<?php
namespace App\Controller;

use App\Repository\GeoLocationRepository;
use App\Repository\RentalRepository;

/**
 * Handles GPS location updates for active rentals.
 * Endpoints for saving location every minute and retrieving route history.
 */
class LocationController
{
    private GeoLocationRepository $geoRepo;
    private RentalRepository $rentalRepo;

    public function __construct()
    {
        $this->geoRepo = new GeoLocationRepository();
        $this->rentalRepo = new RentalRepository();
    }

    /**
     * Saves GPS location for an active rental.
     * Expects POST request with JSON body:
     * {
     *   "rental_id": 123,
     *   "latitude": 55.7558,
     *   "longitude": 37.6173,
     *   "speed": 45.5,
     *   "accuracy": 10
     * }
     *
     * @return void
     */
    public function saveLocation(): void
    {
        header('Content-Type: application/json');

        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $clientId = $this->currentClientId();

        // Check authentication
        if ($clientId === 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            // Parse JSON body
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                return;
            }

            // Validate required fields
            $rentalId = $input['rental_id'] ?? null;
            $latitude = $input['latitude'] ?? null;
            $longitude = $input['longitude'] ?? null;
            $speed = $input['speed'] ?? 0.0;
            $accuracy = $input['accuracy'] ?? 10;

            if ($rentalId === null || $latitude === null || $longitude === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: rental_id, latitude, longitude']);
                return;
            }

            // Verify rental belongs to authenticated client
            $rental = $this->rentalRepo->findById((int)$rentalId);
            if (!$rental || (int)$rental['client_id'] !== $clientId) {
                http_response_code(403);
                echo json_encode(['error' => 'Rental not found or access denied']);
                return;
            }

            // Verify rental is active
            if ($rental['status'] !== 'active') {
                http_response_code(400);
                echo json_encode(['error' => 'Rental is not active']);
                return;
            }

            // Validate coordinates
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid coordinates']);
                return;
            }

            // Save location
            $success = $this->geoRepo->saveGpsLocation(
                (int)$rentalId,
                (int)$rental['car_id'],
                (float)$latitude,
                (float)$longitude,
                (float)$speed,
                (int)$accuracy
            );

            if (!$success) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save location']);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Location saved successfully',
                'rental_id' => $rentalId
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Gets the latest GPS location for an active rental.
     * Query parameter: rental_id
     *
     * @return void
     */
    public function getLatestLocation(): void
    {
        header('Content-Type: application/json');

        $clientId = $this->currentClientId();

        if ($clientId === 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $rentalId = $_GET['rental_id'] ?? null;

            if ($rentalId === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing rental_id parameter']);
                return;
            }

            // Verify rental belongs to client
            $rental = $this->rentalRepo->findById((int)$rentalId);
            if (!$rental || (int)$rental['client_id'] !== $clientId) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }

            $location = $this->geoRepo->getLatestLocation((int)$rentalId);

            if (!$location) {
                http_response_code(404);
                echo json_encode(['error' => 'No location data found']);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $location
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Gets the route (all GPS points) for a rental in the last N minutes.
     * Query parameters: rental_id, minutes (optional, default 60)
     *
     * @return void
     */
    public function getRoute(): void
    {
        header('Content-Type: application/json');

        $clientId = $this->currentClientId();

        if ($clientId === 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $rentalId = $_GET['rental_id'] ?? null;
            $minutes = $_GET['minutes'] ?? 60;

            if ($rentalId === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing rental_id parameter']);
                return;
            }

            // Verify rental belongs to client
            $rental = $this->rentalRepo->findById((int)$rentalId);
            if (!$rental || (int)$rental['client_id'] !== $clientId) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }

            $route = $this->geoRepo->getRoute((int)$rentalId, (int)$minutes);
            $distance = $this->geoRepo->calculateDistance((int)$rentalId, (int)$minutes);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'rental_id' => $rentalId,
                'points_count' => count($route),
                'distance_km' => $distance,
                'data' => $route
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Gets distance traveled for a rental.
     * Query parameters: rental_id, minutes (optional, default 60)
     *
     * @return void
     */
    public function getDistance(): void
    {
        header('Content-Type: application/json');

        $clientId = $this->currentClientId();

        if ($clientId === 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $rentalId = $_GET['rental_id'] ?? null;
            $minutes = $_GET['minutes'] ?? 60;

            if ($rentalId === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing rental_id parameter']);
                return;
            }

            // Verify rental belongs to client
            $rental = $this->rentalRepo->findById((int)$rentalId);
            if (!$rental || (int)$rental['client_id'] !== $clientId) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }

            $distance = $this->geoRepo->calculateDistance((int)$rentalId, (int)$minutes);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'rental_id' => $rentalId,
                'minutes' => $minutes,
                'distance_km' => $distance
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Writes one diagnostic GPS point for the current active rental.
     */
    public function testSaveForActiveRental(): void
    {
        header('Content-Type: application/json');

        $clientId = $this->currentClientId();

        if ($clientId === 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $rental = $this->rentalRepo->findActiveByClientId($clientId);

            if (!$rental) {
                http_response_code(404);
                echo json_encode(['error' => 'No active rental for current user']);
                return;
            }

            $success = $this->geoRepo->saveGpsLocation(
                (int)$rental['id'],
                (int)$rental['car_id'],
                47.0105,
                28.8638,
                0.0,
                10
            );

            if (!$success) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save GPS test location']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'GPS test location inserted',
                'collection' => 'rental_gps_tracks',
                'database' => 'carsharing_nosql',
                'rental_id' => (int)$rental['id'],
                'car_id' => (int)$rental['car_id'],
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Returns the authenticated client identifier from the shared session.
     */
    private function currentClientId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }
}
