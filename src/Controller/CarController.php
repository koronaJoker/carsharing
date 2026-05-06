<?php

namespace App\Controller;

use App\Core\View;
use App\Model\Car;
use App\Repository\CarRepository;
use Throwable;

class CarController
{
    private CarRepository $repo;

    public function __construct(CarRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        $cars = $this->repo->getAll();

        View::render('admin/cars.twig', [
            'cars' => $cars,
        ]);
    }

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
            $this->repo->insert($car->toArray());
            $this->redirectToCars();
        } catch (Throwable $e) {
            View::render('admin/create/car.twig', [
                'car' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

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

    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToCars();
        }

        try {
            $car = $this->createCarFromRequest();
            $this->repo->updateById($id, $car->toArray());
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

    public function delete(int $id): void
    {
        $this->repo->deleteById($id);

        $this->redirectToCars();
    }

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

    private function redirectToCars(): void
    {
        header('Location: ?page=admin/cars');
        exit;
    }
}
