<?php

namespace App\Controller;

use App\Core\View;
use App\Model\Car;
use App\Repository\AuditLogRepository;
use App\Repository\CarRepository;
use Throwable;

/**
 * Handles administrative CRUD actions for cars.
 */
class CarController
{
    /**
     * Car persistence layer.
     */
    private CarRepository $repo;

    /**
     * MongoDB audit log persistence layer.
     */
    private AuditLogRepository $auditLogs;

    /**
     * Creates the controller with a car repository.
     */
    public function __construct(CarRepository $repo, AuditLogRepository $auditLogs)
    {
        $this->repo = $repo;
        $this->auditLogs = $auditLogs;
    }

    /**
     * Shows the list of cars in the admin panel.
     */
    public function index(): void
    {
        $cars = $this->repo->getAll();

        View::render('admin/cars.twig', [
            'cars' => $cars,
        ]);
    }

    /**
     * Shows the car creation form or stores a submitted car.
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('admin/create/car.twig', [
                'car' => [],
            ]);
            return;
        }

        try {
            $car = $this->createCarFromRequest();
            $carId = $this->repo->insert($car->toArray());
            $this->auditLogs->log('admin_car_created', $this->adminPayload([
                'car_id' => $carId,
                'car' => $car->toArray(),
            ]));
            $this->redirectToCars();
        } catch (Throwable $e) {
            View::render('admin/create/car.twig', [
                'car' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Shows the edit form for a car.
     */
    public function edit(int $id): void
    {
        $car = $this->repo->findById($id);

        if ($car === null) {
            http_response_code(404);
            echo 'Car not found';
            return;
        }

        View::render('admin/edit/car.twig', [
            'car' => $car,
        ]);
    }

    /**
     * Updates an existing car from the submitted form.
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToCars();
        }

        try {
            $car = $this->createCarFromRequest();
            $this->repo->updateById($id, $car->toArray());
            $this->auditLogs->log('admin_car_updated', $this->adminPayload([
                'car_id' => $id,
                'car' => $car->toArray(),
            ]));
            $this->redirectToCars();
        } catch (Throwable $e) {
            View::render('admin/edit/car.twig', [
                'car' => [
                    'id' => $id,
                    ...$_POST
                ],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deletes a car by identifier and redirects to the list.
     */
    public function delete(int $id): void
    {
        $car = $this->repo->findById($id);
        $this->repo->deleteById($id);
        $this->auditLogs->log('admin_car_deleted', $this->adminPayload([
            'car_id' => $id,
            'car' => $car,
        ]));

        $this->redirectToCars();
    }

    /**
     * Builds a validated car model from the current request.
     */
    private function createCarFromRequest(): Car
    {
        return new Car(
            $_POST['brand'] ?? '',
            $_POST['model'] ?? '',
            (int)($_POST['year'] ?? 0),
            $_POST['plate_number'] ?? '',
            $_POST['fuel_type'] ?? '',
            $_POST['transmission'] ?? '',
            (float)($_POST['price_per_minute'] ?? 0),
            $_POST['status'] ?? 'available'
        );
    }

    /**
     * Redirects back to the cars administration page.
     */
    private function redirectToCars(): void
    {
        header('Location: ?page=admin/cars');
        exit;
    }

    /**
     * Adds administrator data to an audit payload.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function adminPayload(array $payload): array
    {
        return [
            'admin_id' => (int)($_SESSION['user']['id'] ?? 0),
            'admin_email' => $_SESSION['user']['email'] ?? null,
            'admin' => $_SESSION['user'] ?? null,
            ...$payload,
        ];
    }
}
