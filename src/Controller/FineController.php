<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\FineRepository;
use Throwable;

class FineController
{
    private FineRepository $repo;

    public function __construct(FineRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        View::render('admin/fines.twig', [
            'fines' => $this->repo->getAll(),
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('admin/create/fine.twig', [
                'fine' => ['rating_penalty' => 0.1, 'status' => 'unpaid'],
            ]);
            return;
        }

        try {
            $this->repo->insert($this->fineDataFromRequest());
            $this->redirectToFines();
        } catch (Throwable $e) {
            View::render('admin/create/fine.twig', [
                'fine' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit(int $id): void
    {
        $fine = $this->repo->findById($id);

        if ($fine === null) {
            http_response_code(404);
            echo 'Fine not found';
            return;
        }

        View::render('admin/edit/fine.twig', [
            'fine' => $fine,
        ]);
    }

    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToFines();
        }

        try {
            $this->repo->updateById($id, $this->fineDataFromRequest());
            $this->redirectToFines();
        } catch (Throwable $e) {
            View::render('admin/edit/fine.twig', [
                'fine' => [
                    'id' => $id,
                    ...$_POST,
                ],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete(int $id): void
    {
        $this->repo->deleteById($id);
        $this->redirectToFines();
    }

    private function fineDataFromRequest(): array
    {
        return [
            'client_id' => (int)($_POST['client_id'] ?? 0),
            'rental_id' => ($_POST['rental_id'] ?? '') !== '' ? (int)$_POST['rental_id'] : null,
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'amount' => (float)($_POST['amount'] ?? 0),
            'rating_penalty' => (float)($_POST['rating_penalty'] ?? 0.1),
            'status' => $_POST['status'] ?? 'unpaid',
        ];
    }

    private function redirectToFines(): void
    {
        header('Location: ?page=admin/fines');
        exit;
    }
}
