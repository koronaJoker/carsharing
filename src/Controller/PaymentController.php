<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\PaymentRepository;
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
     * Creates the controller with a payment repository.
     */
    public function __construct(PaymentRepository $repo)
    {
        $this->repo = $repo;
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
            $this->repo->insert($this->paymentDataFromRequest());
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
            $this->repo->updateById($id, $this->paymentDataFromRequest());
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
        $this->repo->deleteById($id);
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
        return $value ? str_replace('T', ' ', $value) : null;
    }

    /**
     * Redirects back to the payments administration page.
     */
    private function redirectToPayments(): void
    {
        header('Location: ?page=admin/payments');
        exit;
    }
}
