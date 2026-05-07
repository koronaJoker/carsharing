<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\AuditLogRepository;
use App\Repository\FineRepository;
use Throwable;

/**
 * Handles administrative CRUD actions for fines.
 */
class FineController
{
    /**
     * Fine persistence layer.
     */
    private FineRepository $repo;

    /**
     * MongoDB audit log persistence layer.
     */
    private AuditLogRepository $auditLogs;

    /**
     * Creates the controller with a fine repository.
     */
    public function __construct(FineRepository $repo, AuditLogRepository $auditLogs)
    {
        $this->repo = $repo;
        $this->auditLogs = $auditLogs;
    }

    /**
     * Shows the list of fines in the admin panel.
     */
    public function index(): void
    {
        View::render('admin/fines.twig', [
            'fines' => $this->repo->getAll(),
        ]);
    }

    /**
     * Shows the fine creation form or stores a submitted fine.
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('admin/create/fine.twig', [
                'fine' => ['rating_penalty' => 0.1, 'status' => 'unpaid'],
            ]);
            return;
        }

        try {
            $fineData = $this->fineDataFromRequest();
            $this->repo->insert($fineData);
            $this->auditLogs->log('admin_fine_created', $this->adminPayload([
                'fine' => $fineData,
            ]));
            $this->redirectToFines();
        } catch (Throwable $e) {
            View::render('admin/create/fine.twig', [
                'fine' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Shows the edit form for a fine.
     */
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

    /**
     * Updates an existing fine from the submitted form.
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToFines();
        }

        try {
            $fineData = $this->fineDataFromRequest();
            $this->repo->updateById($id, $fineData);
            $this->auditLogs->log('admin_fine_updated', $this->adminPayload([
                'fine_id' => $id,
                'fine' => $fineData,
            ]));
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

    /**
     * Deletes a fine by identifier and redirects to the list.
     */
    public function delete(int $id): void
    {
        $fine = $this->repo->findById($id);
        $this->repo->deleteById($id);
        $this->auditLogs->log('admin_fine_deleted', $this->adminPayload([
            'fine_id' => $id,
            'fine' => $fine,
        ]));
        $this->redirectToFines();
    }

    /**
     * Normalizes fine form data for persistence.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Redirects back to the fines administration page.
     */
    private function redirectToFines(): void
    {
        header('Location: ?page=admin/fines');
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
