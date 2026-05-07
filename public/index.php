<?php

/**
 * Application front controller.
 *
 * Boots Composer, starts the session, and dispatches public and admin routes.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../src', 'data.env');
$dotenv->safeLoad();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Bucharest');

$sessionPath = __DIR__ . '/../tmp/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);
session_start();

use App\Controller\AuthController;
use App\Controller\CarController;
use App\Controller\ClientController;
use App\Controller\FineController;
use App\Controller\LocationController;
use App\Controller\PaymentController;
use App\Controller\RentalController;
use App\Repository\PaymentRepository;
use App\Repository\AuditLogRepository;
use App\Repository\CarRepository;
use App\Repository\ClientAddressRepository;
use App\Repository\ClientRepository;
use App\Repository\RentalRepository;
use App\Repository\FineRepository;

$page = trim($_GET['page'] ?? 'login', '/');
$auditLogs = new AuditLogRepository();

$auth = new AuthController(
    new ClientRepository(),
    new CarRepository(),
    new RentalRepository(),
    new PaymentRepository(),
    new ClientAddressRepository(),
    $auditLogs
);

/**
 * @var array<string, callable(): void> $publicRoutes Public page route handlers.
 */
$publicRoutes = [
    '' => fn() => $auth->login(),
    'login' => fn() => $auth->login(),
    'registration' => fn() => $auth->registration(),
    'register' => fn() => $auth->registration(),
    'home' => fn() => $auth->cars(),
    'logout' => fn() => $auth->logout(),
    'cars' => fn() => $auth->cars(),
    'rent' => fn() => $auth->rent(),
    'payment' => fn() => $auth->payment(),
    'profile' => fn() => $auth->profile(),
    'adresses' => fn() => $auth->adresses(),
    'adress/action' => fn() => $auth->adressAction(),
    'my_car' => fn() => $auth->myCar(),
    'car/action' => fn() => $auth->carAction(),
    'mongo/test' => function () use ($auditLogs): void {
        $ok = $auditLogs->testConnection();
        echo $ok ? 'MongoDB test document inserted' : 'MongoDB test failed: ' . ($_SESSION['mongo_error'] ?? 'unknown error');
    },
    'mongo/gps-test' => fn() => (new LocationController())->testSaveForActiveRental(),
];

if (str_starts_with($page, 'api/location/')) {
    $locations = new LocationController();

    match ($page) {
        'api/location/save' => $locations->saveLocation(),
        'api/location/latest' => $locations->getLatestLocation(),
        'api/location/route' => $locations->getRoute(),
        'api/location/distance' => $locations->getDistance(),
        default => null,
    };

    if (!in_array($page, ['api/location/save', 'api/location/latest', 'api/location/route', 'api/location/distance'], true)) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }

    exit;
}

if (isset($publicRoutes[$page])) {
    $publicRoutes[$page]();
    exit;
}

/**
 * @var array<string, object> $adminControllers Admin route prefixes mapped to CRUD controllers.
 */
$adminControllers = [
    'admin/cars' => new CarController(new CarRepository(), $auditLogs),
    'admin/clients' => new ClientController(new ClientRepository(), $auditLogs),
    'admin/payments' => new PaymentController(new PaymentRepository(), $auditLogs),
    'admin/rentals' => new RentalController(new RentalRepository(), $auditLogs),
    'admin/fines' => new FineController(new FineRepository(), $auditLogs),
];

foreach ($adminControllers as $prefix => $controller) {
    if (!str_starts_with($page, $prefix)) {
        continue;
    }

    $auth->requireAdmin();

    match ($page) {
        $prefix => $controller->index(),
        $prefix . '/create' => $controller->create(),
        $prefix . '/edit' => $controller->edit((int)($_GET['id'] ?? 0)),
        $prefix . '/update' => $controller->update((int)($_GET['id'] ?? 0)),
        $prefix . '/delete' => $controller->delete((int)($_GET['id'] ?? 0)),
        default => null,
    };

    if (!in_array($page, [$prefix, $prefix . '/create', $prefix . '/edit', $prefix . '/update', $prefix . '/delete'], true)) {
        http_response_code(404);
        echo '404';
    }

    exit;
}

http_response_code(404);
echo '404';
