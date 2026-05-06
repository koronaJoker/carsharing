<?php

require_once __DIR__ . '/../vendor/autoload.php';

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
use App\Controller\PaymentController;
use App\Controller\RentalController;
use App\Repository\AdminRepository;
use App\Repository\PaymentRepository;
use App\Repository\CarRepository;
use App\Repository\ClientRepository;
use App\Repository\RentalRepository;
use App\Repository\FineRepository;

$page = trim($_GET['page'] ?? 'login', '/');

$auth = new AuthController(
    new ClientRepository(),
    new AdminRepository(),
    new CarRepository(),
    new RentalRepository(),
    new PaymentRepository()
);

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
    'my_car' => fn() => $auth->myCar(),
    'car/action' => fn() => $auth->carAction(),
];

if (isset($publicRoutes[$page])) {
    $publicRoutes[$page]();
    exit;
}

$adminControllers = [
    'admin/cars' => new CarController(new CarRepository()),
    'admin/clients' => new ClientController(new ClientRepository()),
    'admin/payments' => new PaymentController(new PaymentRepository()),
    'admin/rentals' => new RentalController(new RentalRepository()),
    'admin/fines' => new FineController(new FineRepository()),
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
