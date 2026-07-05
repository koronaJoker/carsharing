<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminRecordController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', [
        'title' => 'Главная страница',
        'active' => 'home',
    ]);
});

Route::get('/rent', [RentalController::class, 'create'])
    ->middleware('auth')
    ->name('rent.create');

Route::post('/payment/preview', [RentalController::class, 'showPayment'])
    ->middleware('auth')
    ->name('payment.preview');

Route::post('/payment', [RentalController::class, 'processPayment'])
    ->middleware('auth')
    ->name('payment.process');

Route::get('/rentals/{rental}/active', [RentalController::class, 'active'])
    ->middleware('auth')
    ->name('rentals.active');

Route::post('/rentals/{rental}/command', [RentalController::class, 'command'])
    ->middleware('auth')
    ->name('rentals.command');

Route::post('/rentals/{rental}/finish', [RentalController::class, 'finish'])
    ->middleware('auth')
    ->name('rentals.finish');

Route::get('/cars', [CarController::class, 'index'])->name('cars');

Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('profile');

Route::get('/profile/edit', [ProfileController::class, 'edit'])
    ->middleware('auth')
    ->name('profile.edit');

Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('profile.update');

Route::delete('/profile', [ProfileController::class, 'destroy'])
    ->middleware('auth')
    ->name('profile.destroy');

Route::get('/payment', [RentalController::class, 'payment'])
    ->middleware('auth')
    ->name('payment.show');

Route::get('/dashboard', function () {
    return redirect()->route('profile');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        $resources = 'users|rentals|cars|payments|fines';

        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/{resource}', [AdminRecordController::class, 'index'])->where('resource', $resources)->name('records.index');
        Route::get('/{resource}/create', [AdminRecordController::class, 'create'])->where('resource', $resources)->name('records.create');
        Route::post('/{resource}', [AdminRecordController::class, 'store'])->where('resource', $resources)->name('records.store');
        Route::get('/{resource}/{record}', [AdminRecordController::class, 'show'])->where('resource', $resources)->whereNumber('record')->name('records.show');
        Route::get('/{resource}/{record}/edit', [AdminRecordController::class, 'edit'])->where('resource', $resources)->whereNumber('record')->name('records.edit');
        Route::put('/{resource}/{record}', [AdminRecordController::class, 'update'])->where('resource', $resources)->whereNumber('record')->name('records.update');
        Route::delete('/{resource}/{record}', [AdminRecordController::class, 'destroy'])->where('resource', $resources)->whereNumber('record')->name('records.destroy');
    });

require __DIR__.'/auth.php';
