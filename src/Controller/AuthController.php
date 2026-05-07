<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\CarRepository;
use App\Repository\AuditLogRepository;
use App\Repository\ClientAddressRepository;
use App\Repository\ClientRepository;
use App\Repository\PaymentRepository;
use App\Repository\RentalRepository;
use App\Validation\AuthValidator;
use App\Validation\PaymentValidator;
use App\Validation\RentalValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Handles authentication and client-facing rental workflows.
 */
class AuthController
{
    /**
     * Creates the controller with repositories needed for user workflows.
     */
    public function __construct(
        private ClientRepository $clients,
        private CarRepository $cars,
        private RentalRepository $rentals,
        private PaymentRepository $payments,
        private ClientAddressRepository $adresses,
        private AuditLogRepository $auditLogs
    ) {
    }

    /**
     * Shows the login form or authenticates submitted credentials.
     */
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
            $this->auditLogs->log('login', [
                'client_id' => $this->clientId(),
                'login' => $credentials['login'],
                'user' => $_SESSION['user'] ?? null,
            ]);
            $this->redirect($this->isAdmin() ? '?page=admin/cars' : '?page=cars');
        } catch (Throwable $e) {
            View::render('login.twig', [
                'error' => $e->getMessage(),
                'old' => ['login' => $_POST['login'] ?? ''],
            ]);
        }
    }

    /**
     * Shows the registration form or creates a new client account.
     */
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

            $this->auditLogs->log('registration', [
                'client_id' => $clientId,
                'email' => $clientData['email'],
                'user' => [
                    'name' => $clientData['full_name'],
                    'email' => $clientData['email'],
                ],
            ]);

            $this->redirect('?page=cars');
        } catch (Throwable $e) {
            View::render('registration.twig', [
                'error' => $e->getMessage(),
                'old' => $_POST,
            ]);
        }
    }

    /**
     * Clears the session and redirects to login.
     */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('?page=login');
    }

    /**
     * Shows available cars with filtering options.
     */
    public function cars(): void
    {
        $this->requireAuth();
        $filters = $this->carFiltersFromRequest();
        $filterOptions = $this->cars->getFilterOptions();

        View::render('cars.twig', [
            'cars' => $this->withCarImages($this->cars->getAvailableFiltered($filters)),
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'priceLimit' => $filters['max_price'] !== '' ? $filters['max_price'] : $filterOptions['maxPrice'],
            'activeRental' => $this->rentals->findActiveByClientId($this->clientId()),
        ]);
    }

    /**
     * Shows the rental form or creates a rental for the selected car.
     */
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
            $this->auditLogs->log('rental_created', [
                'client_id' => $this->clientId(),
                'rental_id' => $rentalId,
                'car_id' => (int)$car['id'],
                'user' => $_SESSION['user'] ?? null,
                'car' => $car,
                'total_cost' => $totalCost,
            ]);
            $this->redirect('?page=payment&rental_id=' . $rentalId);
        } catch (Throwable $e) {
            View::render('rent.twig', [
                'error' => $e->getMessage(),
                'car' => $car,
                'old' => $_POST,
            ]);
        }
    }

    /**
     * Shows the payment form or records payment for a rental.
     */
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
                'paid_at' => $this->currentLocalDateTime(),
            ]);

            $this->auditLogs->log('payment_created', [
                'client_id' => $this->clientId(),
                'rental_id' => $rentalId,
                'user' => $_SESSION['user'] ?? null,
                'amount' => (float)$rental['total_cost'],
                'payment_method' => $paymentData['payment_method'],
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

    /**
     * Shows the authenticated client's profile.
     */
    public function profile(): void
    {
        $this->requireAuth();
        $client = $this->clients->findById($this->clientId());

        View::render('profile.twig', ['client' => $client]);
    }

    /**
     * Shows saved client addresses.
     */
    public function adresses(): void
    {
        $this->requireAuth();

        View::render('adress.twig', [
            'adresses' => $this->withMapLinks($this->adresses->findByClientId($this->clientId())),
            'old' => $_POST,
        ]);
    }

    /**
     * Creates, updates, or deletes a client address from form input.
     */
    public function adressAction(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=adresses');
        }

        try {
            $action = $_POST['action'] ?? 'create';
            $id = (int)($_POST['id'] ?? 0);

            if ($action === 'delete' && $id > 0) {
                $this->adresses->deleteForClient($id, $this->clientId());
                $this->redirect('?page=adresses');
            }

            $adressData = $this->adressDataFromRequest();

            if ($action === 'update' && $id > 0) {
                $this->adresses->updateForClient($id, $this->clientId(), $adressData);
            } else {
                $this->adresses->insert([
                    'client_id' => $this->clientId(),
                    ...$adressData,
                ]);
            }

            $this->redirect('?page=adresses');
        } catch (Throwable $e) {
            View::render('adress.twig', [
                'adresses' => $this->withMapLinks($this->adresses->findByClientId($this->clientId())),
                'error' => $e->getMessage(),
                'old' => $_POST,
            ]);
        }
    }

    /**
     * Shows the client's active rental and related payment.
     */
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

    /**
     * Handles actions for the client's active car rental.
     */
    public function carAction(): void
    {
        $this->requireAuth();

        if (($_POST['action'] ?? '') === 'finish') {
            $rental = $this->rentals->findActiveByClientId($this->clientId());
            if ($rental) {
                $this->rentals->finishActiveForClient($this->clientId());
                $this->cars->updateStatus((int)$rental['car_id'], 'available');
                $this->auditLogs->log('rental_finished', [
                    'client_id' => $this->clientId(),
                    'rental_id' => (int)$rental['id'],
                    'car_id' => (int)$rental['car_id'],
                    'user' => $_SESSION['user'] ?? null,
                    'car' => $rental,
                ]);
            }
        }

        $this->redirect('?page=my_car');
    }

    /**
     * Ensures the current session belongs to an administrator.
     */
    public function requireAdmin(): void
    {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            http_response_code(403);
            echo 'Доступ только для администратора';
            exit;
        }
    }

    /**
     * Authenticates credentials and stores the user in the session.
     */
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

    /**
     * Redirects unauthenticated users to the login page.
     */
    private function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('?page=login');
        }
    }

    /**
     * Checks whether the current session contains a user.
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Checks whether the current session belongs to an administrator.
     */
    private function isAdmin(): bool
    {
        return ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    /**
     * Returns the current client identifier from the session.
     */
    private function clientId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    /**
     * Sends an HTTP redirect and stops execution.
     */
    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    /**
     * Reads car filter values from the current request.
     *
     * @return array<string, string>
     */
    private function carFiltersFromRequest(): array
    {
        return [
            'brand' => trim($_GET['brand'] ?? ''),
            'fuel_type' => trim($_GET['fuel_type'] ?? ''),
            'transmission' => trim($_GET['transmission'] ?? ''),
            'year' => trim($_GET['year'] ?? ''),
            'max_price' => trim($_GET['max_price'] ?? ''),
            'sort' => trim($_GET['sort'] ?? ''),
        ];
    }

    /**
     * Adds image filenames to car rows.
     *
     * @param array<int, array<string, mixed>> $cars
     * @return array<int, array<string, mixed>>
     */
    private function withCarImages(array $cars): array
    {
        foreach ($cars as &$car) {
            $car['image'] = $this->carImage((string)$car['brand'], (string)$car['model']);
        }

        return $cars;
    }

    /**
     * Adds Google Maps URLs to address rows.
     *
     * @param array<int, array<string, mixed>> $adresses
     * @return array<int, array<string, mixed>>
     */
    private function withMapLinks(array $adresses): array
    {
        foreach ($adresses as &$adress) {
            $lat = (string)$adress['latitude'];
            $lng = (string)$adress['longitude'];
            $adress['maps_url'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng);
        }

        return $adresses;
    }

    /**
     * Validates and normalizes address form input.
     *
     * @return array<string, mixed>
     */
    private function adressDataFromRequest(): array
    {
        $title = trim($_POST['title'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $coordinates = trim($_POST['coordinates'] ?? '');

        if (mb_strlen($title) < 2) {
            throw new \InvalidArgumentException('Название адреса должно содержать минимум 2 символа.');
        }

        if (mb_strlen($address) < 5) {
            throw new \InvalidArgumentException('Адрес должен содержать минимум 5 символов.');
        }

        if (!preg_match('/^\s*(-?\d+(?:[.,]\d+)?)\s*,\s*(-?\d+(?:[.,]\d+)?)\s*$/', $coordinates, $matches)) {
            throw new \InvalidArgumentException('Координаты должны быть в формате: 47.049769001929626, 28.864979562989657');
        }

        $latitude = str_replace(',', '.', $matches[1]);
        $longitude = str_replace(',', '.', $matches[2]);

        if (!is_numeric($latitude) || (float)$latitude < -90 || (float)$latitude > 90) {
            throw new \InvalidArgumentException('Широта должна быть числом от -90 до 90.');
        }

        if (!is_numeric($longitude) || (float)$longitude < -180 || (float)$longitude > 180) {
            throw new \InvalidArgumentException('Долгота должна быть числом от -180 до 180.');
        }

        return [
            'title' => $title,
            'address' => $address,
            'latitude' => (float)$latitude,
            'longitude' => (float)$longitude,
        ];
    }

    /**
     * Resolves a car image filename for the given brand and model.
     */
    private function carImage(string $brand, string $model): string
    {
        $key = mb_strtolower($brand . ' ' . $model);
        $images = [
            'dacia logan' => 'dacia-logan.webp',
            'dacia sandero' => 'dacia-sandero.webp',
            'renault clio' => 'renault-clio.webp',
            'volkswagen polo' => 'volkswagen-polo.webp',
            'skoda octavia' => 'skoda-octavia.webp',
            'toyota corolla' => 'toyota-corolla.webp',
            'hyundai i30' => 'hyundai-i30.webp',
            'kia ceed' => 'kia-ceed.webp',
            'nissan leaf' => 'nissan-leaf.webp',
            'bmw 1 series' => 'bmw-1-series.webp',
        ];

        return $images[$key] ?? 'car-placeholder.webp';
    }

    /**
     * Returns the current local datetime formatted for PostgreSQL timestamp fields.
     */
    private function currentLocalDateTime(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'Europe/Bucharest')))->format('Y-m-d H:i:s');
    }
}
