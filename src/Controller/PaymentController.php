<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\PaymentRepository;
use Throwable;

class PaymentController
{
    private PaymentRepository $repo;

    public function __construct(PaymentRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        View::render('admin/payments.twig', [
            'payments' => $this->repo->getAll(),
        ]);
    }

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

    public function delete(int $id): void
    {
        $this->repo->deleteById($id);
        $this->redirectToPayments();
    }

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

    private function dateTimeOrNull(?string $value): ?string
    {
        return $value ? str_replace('T', ' ', $value) : null;
    }

    private function redirectToPayments(): void
    {
        header('Location: ?page=admin/payments');
        exit;
    }
}
