<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\ClientRepository;
use Throwable;

class ClientController
{
    private ClientRepository $repo;

    public function __construct(ClientRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        View::render('admin/clients.twig', [
            'clients' => $this->repo->getAll(),
        ]);
    }

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

    public function delete(int $id): void
    {
        $this->repo->deleteById($id);
        $this->redirectToClients();
    }

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

    private function redirectToClients(): void
    {
        header('Location: ?page=admin/clients');
        exit;
    }
}
