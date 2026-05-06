<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\CarRepository;
use App\Repository\ClientRepository;
use App\Repository\PaymentRepository;
use App\Repository\RentalRepository;
use App\Validation\AuthValidator;
use App\Validation\PaymentValidator;
use App\Validation\RentalValidator;
use Throwable;

class AuthController
{
    public function __construct(
        private ClientRepository $clients,
        private CarRepository $cars,
        private RentalRepository $rentals,
        private PaymentRepository $payments
    ) {
    }

    public function login(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('?page=cars');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('login.twig');
            return;
        }

        try {
            $credentials = AuthValidator::login($_POST);
            $this->authenticate($credentials['login'], $credentials['password']);
            $this->redirect($this->isAdmin() ? '?page=admin/cars' : '?page=cars');
        } catch (Throwable $e) {
            View::render('login.twig', [
                'error' => $e->getMessage(),
                'old' => ['login' => $_POST['login'] ?? ''],
            ]);
        }
    }

    public function registration(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('?page=cars');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('registration.twig');
            return;
        }

        try {
            $clientData = AuthValidator::registration($_POST);

            if ($this->clients->findByEmail($clientData['email']) !== null) {
                throw new \InvalidArgumentException('Пользователь с таким email уже зарегистрирован.');
            }

            $clientId = $this->clients->insert($clientData);
            $_SESSION['user'] = [
                'id' => $clientId,
                'name' => $clientData['full_name'],
                'email' => $clientData['email'],
                'role' => 'client',
            ];

            $this->redirect('?page=cars');
        } catch (Throwable $e) {
            View::render('registration.twig', [
                'error' => $e->getMessage(),
                'old' => $_POST,
            ]);
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('?page=login');
    }

    public function cars(): void
    {
        $this->requireAuth();

        View::render('cars.twig', [
            'cars' => $this->cars->getAvailable(),
            'activeRental' => $this->rentals->findActiveByClientId($this->clientId()),
        ]);
    }

    public function rent(): void
    {
        $this->requireAuth();

        $carId = (int)($_GET['car_id'] ?? $_POST['car_id'] ?? 0);
        $car = $carId > 0 ? $this->cars->findById($carId) : null;

        if ($car === null || $car['status'] !== 'available') {
            View::render('rent.twig', [
                'error' => 'Выберите доступный автомобиль.',
                'car' => null,
                'old' => $_POST,
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('rent.twig', ['car' => $car]);
            return;
        }

        try {
            $rentalData = RentalValidator::rent($_POST);
            $minutes = max(1, (strtotime($rentalData['end_time']) - strtotime($rentalData['start_time'])) / 60);
            $totalCost = round($minutes * (float)$car['price_per_minute'], 2);

            $rentalId = $this->rentals->insert([
                'client_id' => $this->clientId(),
                'car_id' => $car['id'],
                'start_time' => $rentalData['start_time'],
                'end_time' => $rentalData['end_time'],
                'total_cost' => $totalCost,
                'status' => 'active',
            ]);

            $this->cars->updateStatus((int)$car['id'], 'rented');
            $this->redirect('?page=payment&rental_id=' . $rentalId);
        } catch (Throwable $e) {
            View::render('rent.twig', [
                'error' => $e->getMessage(),
                'car' => $car,
                'old' => $_POST,
            ]);
        }
    }

    public function payment(): void
    {
        $this->requireAuth();

        $rentalId = (int)($_GET['rental_id'] ?? $_POST['rental_id'] ?? 0);
        $rental = $rentalId > 0 ? $this->rentals->findById($rentalId) : null;

        if ($rental === null || (int)$rental['client_id'] !== $this->clientId()) {
            http_response_code(404);
            View::render('payment.twig', ['error' => 'Аренда не найдена.', 'rental' => null]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('payment.twig', ['rental' => $rental]);
            return;
        }

        try {
            $paymentData = PaymentValidator::card($_POST);
            $this->payments->insert([
                'rental_id' => $rentalId,
                'amount' => $rental['total_cost'],
                'payment_method' => $paymentData['payment_method'],
                'payment_status' => 'paid',
                'paid_at' => date('Y-m-d H:i:s'),
            ]);

            $this->redirect('?page=my_car');
        } catch (Throwable $e) {
            View::render('payment.twig', [
                'error' => $e->getMessage(),
                'rental' => $rental,
                'old' => $_POST,
            ]);
        }
    }

    public function profile(): void
    {
        $this->requireAuth();
        $client = $this->clients->findById($this->clientId());

        View::render('profile.twig', ['client' => $client]);
    }

    public function myCar(): void
    {
        $this->requireAuth();
        $rental = $this->rentals->findActiveByClientId($this->clientId());

        View::render('my_car.twig', [
            'rental' => $rental,
            'car' => $rental,
            'payment' => $rental ? $this->payments->findByRentalId((int)$rental['id']) : null,
        ]);
    }

    public function carAction(): void
    {
        $this->requireAuth();

        if (($_POST['action'] ?? '') === 'finish') {
            $rental = $this->rentals->findActiveByClientId($this->clientId());
            if ($rental) {
                $this->rentals->finishActiveForClient($this->clientId());
                $this->cars->updateStatus((int)$rental['car_id'], 'available');
            }
        }

        $this->redirect('?page=my_car');
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            http_response_code(403);
            echo 'Доступ только для администратора';
            exit;
        }
    }

    private function authenticate(string $login, string $password): void
    {
        $client = $this->clients->findByLogin($login);

        if ($client && password_verify($password, $client['password_hash'])) {
            $_SESSION['user'] = [
                'id' => (int)$client['id'],
                'name' => $client['full_name'],
                'email' => $client['email'],
                'role' => $client['role'] ?? 'client',
            ];
            return;
        }

        throw new \InvalidArgumentException('Неверный логин или пароль.');
    }

    private function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('?page=login');
        }
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }

    private function isAdmin(): bool
    {
        return ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    private function clientId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
