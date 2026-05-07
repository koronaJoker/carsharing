<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\AuditLogRepository;
use App\Repository\PaymentRepository;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Handles administrative CRUD actions for payments.
 */
class PaymentController
{
    /**
     * Payment persistence layer.
     */
    private PaymentRepository $repo;

    /**
     * MongoDB audit log persistence layer.
     */
    private AuditLogRepository $auditLogs;

    /**
     * Creates the controller with a payment repository.
     */
    public function __construct(PaymentRepository $repo, AuditLogRepository $auditLogs)
    {
        $this->repo = $repo;
        $this->auditLogs = $auditLogs;
    }

    /**
     * Shows the list of payments in the admin panel.
     */
    public function index(): void
    {
        View::render('admin/payments.twig', [
            'payments' => $this->repo->getAll(),
        ]);
    }

    /**
     * Shows the payment creation form or stores a submitted payment.
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('admin/create/payment.twig', [
                'payment' => ['payment_method' => 'card', 'payment_status' => 'pending'],
            ]);
            return;
        }

        try {
            $paymentData = $this->paymentDataFromRequest();
            $paymentId = $this->repo->insert($paymentData);
            $this->auditLogs->log('admin_payment_created', $this->adminPayload([
                'payment_id' => $paymentId,
                'payment' => $paymentData,
            ]));
            $this->redirectToPayments();
        } catch (Throwable $e) {
            View::render('admin/create/payment.twig', [
                'payment' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Shows the edit form for a payment.
     */
    public function edit(int $id): void
    {
        $payment = $this->repo->findById($id);

        if ($payment === null) {
            http_response_code(404);
            echo 'Payment not found';
            return;
        }

        View::render('admin/edit/payment.twig', [
            'payment' => $payment,
        ]);
    }

    /**
     * Updates an existing payment from the submitted form.
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPayments();
        }

        try {
            $paymentData = $this->paymentDataFromRequest();
            $this->repo->updateById($id, $paymentData);
            $this->auditLogs->log('admin_payment_updated', $this->adminPayload([
                'payment_id' => $id,
                'payment' => $paymentData,
            ]));
            $this->redirectToPayments();
        } catch (Throwable $e) {
            View::render('admin/edit/payment.twig', [
                'payment' => [
                    'id' => $id,
                    ...$_POST,
                ],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deletes a payment by identifier and redirects to the list.
     */
    public function delete(int $id): void
    {
        $payment = $this->repo->findById($id);
        $this->repo->deleteById($id);
        $this->auditLogs->log('admin_payment_deleted', $this->adminPayload([
            'payment_id' => $id,
            'payment' => $payment,
        ]));
        $this->redirectToPayments();
    }

    /**
     * Normalizes payment form data for persistence.
     *
     * @return array<string, mixed>
     */
    private function paymentDataFromRequest(): array
    {
        return [
            'rental_id' => (int)($_POST['rental_id'] ?? 0),
            'amount' => (float)($_POST['amount'] ?? 0),
            'payment_method' => $_POST['payment_method'] ?? 'card',
            'payment_status' => $_POST['payment_status'] ?? 'pending',
            'paid_at' => $this->dateTimeOrNull($_POST['paid_at'] ?? null),
        ];
    }

    /**
     * Converts a datetime-local value to a database datetime string.
     */
    private function dateTimeOrNull(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return (new DateTimeImmutable($value, new DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'Europe/Bucharest')))->format('Y-m-d H:i:s');
    }

    /**
     * Redirects back to the payments administration page.
     */
    private function redirectToPayments(): void
    {
        header('Location: ?page=admin/payments');
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
