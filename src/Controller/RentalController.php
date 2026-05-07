<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\RentalRepository;
use Throwable;

/**
 * Handles administrative CRUD actions for rentals.
 */
class RentalController
{
    /**
     * Rental persistence layer.
     */
    private RentalRepository $repo;

    /**
     * Creates the controller with a rental repository.
     */
    public function __construct(RentalRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Shows the list of rentals in the admin panel.
     */
    public function index(): void
    {
        View::render('admin/rentals.twig', [
            'rentals' => $this->repo->getAll(),
        ]);
    }

    /**
     * Shows the rental creation form or stores a submitted rental.
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('admin/create/rental.twig', [
                'rental' => ['total_cost' => 0, 'status' => 'active'],
            ]);
            return;
        }

        try {
            $this->repo->insert($this->rentalDataFromRequest());
            $this->redirectToRentals();
        } catch (Throwable $e) {
            View::render('admin/create/rental.twig', [
                'rental' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Shows the edit form for a rental.
     */
    public function edit(int $id): void
    {
        $rental = $this->repo->findById($id);

        if ($rental === null) {
            http_response_code(404);
            echo 'Rental not found';
            return;
        }

        View::render('admin/edit/rental.twig', [
            'rental' => $rental,
        ]);
    }

    /**
     * Updates an existing rental from the submitted form.
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToRentals();
        }

        try {
            $this->repo->updateById($id, $this->rentalDataFromRequest());
            $this->redirectToRentals();
        } catch (Throwable $e) {
            View::render('admin/edit/rental.twig', [
                'rental' => [
                    'id' => $id,
                    ...$_POST,
                ],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deletes a rental by identifier and redirects to the list.
     */
    public function delete(int $id): void
    {
        $this->repo->deleteById($id);
        $this->redirectToRentals();
    }

    /**
     * Normalizes rental form data for persistence.
     *
     * @return array<string, mixed>
     */
    private function rentalDataFromRequest(): array
    {
        return [
            'client_id' => (int)($_POST['client_id'] ?? 0),
            'car_id' => (int)($_POST['car_id'] ?? 0),
            'start_time' => $this->dateTimeOrNull($_POST['start_time'] ?? null),
            'end_time' => $this->dateTimeOrNull($_POST['end_time'] ?? null),
            'total_cost' => (float)($_POST['total_cost'] ?? 0),
            'status' => $_POST['status'] ?? 'active',
        ];
    }

    /**
     * Converts a datetime-local value to a database datetime string.
     */
    private function dateTimeOrNull(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    /**
     * Redirects back to the rentals administration page.
     */
    private function redirectToRentals(): void
    {
        header('Location: ?page=admin/rentals');
        exit;
    }
}
