<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\ClientRepository;
use Throwable;

/**
 * Handles administrative CRUD actions for clients.
 */
class ClientController
{
    /**
     * Client persistence layer.
     */
    private ClientRepository $repo;

    /**
     * Creates the controller with a client repository.
     */
    public function __construct(ClientRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Shows the list of clients in the admin panel.
     */
    public function index(): void
    {
        View::render('admin/clients.twig', [
            'clients' => $this->repo->getAll(),
        ]);
    }

    /**
     * Shows the client creation form or stores a submitted client.
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('admin/create/client.twig', [
                'client' => ['driver_rating' => 5],
            ]);
            return;
        }

        try {
            $this->repo->insert($this->clientDataFromRequest());
            $this->redirectToClients();
        } catch (Throwable $e) {
            View::render('admin/create/client.twig', [
                'client' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Shows the edit form for a client.
     */
    public function edit(int $id): void
    {
        $client = $this->repo->findById($id);

        if ($client === null) {
            http_response_code(404);
            echo 'Client not found';
            return;
        }

        View::render('admin/edit/client.twig', [
            'client' => $client,
        ]);
    }

    /**
     * Updates an existing client from the submitted form.
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToClients();
        }

        $client = $this->repo->findById($id);

        if ($client === null) {
            http_response_code(404);
            echo 'Client not found';
            return;
        }

        try {
            $this->repo->updateById($id, $this->clientDataFromRequest($client['password_hash'], $client['role'] ?? 'client'));
            $this->redirectToClients();
        } catch (Throwable $e) {
            View::render('admin/edit/client.twig', [
                'client' => [
                    'id' => $id,
                    ...$_POST,
                    'password_hash' => $client['password_hash'],
                ],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deletes a client by identifier and redirects to the list.
     */
    public function delete(int $id): void
    {
        $this->repo->deleteById($id);
        $this->redirectToClients();
    }

    /**
     * Normalizes client form data for persistence.
     *
     * @return array<string, mixed>
     */
    private function clientDataFromRequest(?string $currentPasswordHash = null, string $currentRole = 'client'): array
    {
        $password = trim($_POST['password'] ?? '');

        return [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'idnp' => trim($_POST['idnp'] ?? ''),
            'driver_license' => trim($_POST['driver_license'] ?? ''),
            'driver_rating' => (float)($_POST['driver_rating'] ?? 5),
            'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : $currentPasswordHash,
            'role' => $_POST['role'] ?? $currentRole,
        ];
    }

    /**
     * Redirects back to the clients administration page.
     */
    private function redirectToClients(): void
    {
        header('Location: ?page=admin/clients');
        exit;
    }
}
