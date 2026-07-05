<?php

use App\Models\Car;
use App\Models\Rental;
use App\Models\User;
use App\Services\MongoTelemetryService;

test('user can register, choose a car, pay and finish the rental', function () {
    $this->app->instance(MongoTelemetryService::class, new class extends MongoTelemetryService
    {
        public function logEvent(string $type, string $message, array $payload = []): void
        {
            // MongoDB is external telemetry and is not part of this feature test.
        }
    });

    $car = Car::create([
        'brand' => 'Toyota Corolla',
        'year' => 2024,
        'number_plate' => 'TEST001',
        'fuel_type' => 'hybrid',
        'transmission' => 'automatic',
        'price_per_minute' => 2.50,
        'status' => 'available',
        'image_url' => '/images/toyota-corolla.webp',
    ]);

    $registrationResponse = $this->post('/register', [
        'name' => 'Test Driver',
        'email' => 'driver@example.com',
        'phone' => '+37360123456',
        'idnp' => '2000000000001',
        'driver_license' => 'DL123456',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $registrationResponse->assertRedirect(route('profile', absolute: false));
    $this->assertAuthenticated();
    $user = auth()->user();

    $this->get(route('cars'))
        ->assertOk()
        ->assertSee('Toyota Corolla');

    $this->get(route('rent.create', ['car_id' => $car->id]))
        ->assertOk()
        ->assertViewIs('rent')
        ->assertViewHas('car', fn (Car $selectedCar) => $selectedCar->is($car));

    $rentalData = [
        'car_id' => $car->id,
        'branch' => 'airport',
        'start_time' => '2026-07-01 10:00',
        'end_time' => '2026-07-01 11:00',
    ];

    $this->post(route('payment.preview'), $rentalData)
        ->assertRedirect(route('payment.show'));

    $this->get(route('payment.show'))
        ->assertOk()
        ->assertViewIs('payment')
        ->assertViewHas('minutes', 60)
        ->assertViewHas('total', 150.0);

    $paymentResponse = $this->post(route('payment.process'), [
        ...$rentalData,
        'card_name' => 'Test Driver',
        'card_number' => '4242 4242 4242 4242',
        'expiry' => '12/30',
        'cvc' => '123',
    ]);

    $rental = Rental::query()->sole();

    $paymentResponse->assertRedirect(route('rentals.active', $rental));
    expect($rental->user_id)->toBe($user->id)
        ->and($rental->car_id)->toBe($car->id)
        ->and($rental->status)->toBe('active')
        ->and($rental->total_cost)->toBe(150.0);

    $this->assertDatabaseHas('payments', [
        'rental_id' => $rental->id,
        'amount' => 150,
        'payment_method' => 'card',
        'payment_status' => 'paid',
    ]);
    expect($car->fresh()->status)->toBe('busy');

    $this->get(route('rentals.active', $rental))
        ->assertOk()
        ->assertViewIs('rental-active')
        ->assertSee('active-rental-card', false)
        ->assertSee('src="'.asset('images/toyota-corolla.webp').'"', false)
        ->assertSee('Помигать')
        ->assertSee('Прогреть')
        ->assertSee('Открыть двери');

    $this->post(route('rentals.command', $rental), [
        'action' => 'flash',
    ])->assertSessionHas('command_action', 'flash');

    $this->post(route('rentals.command', $rental), [
        'action' => 'toggle_lock',
    ])->assertSessionHas('rentals.'.$rental->id.'.locked', false);

    $this->post(route('rentals.finish', $rental), [
        'confirm_finish' => '1',
    ])->assertRedirect(route('cars'));

    expect($rental->fresh()->status)->toBe('completed')
        ->and($rental->fresh()->end_time)->not->toBeNull()
        ->and($car->fresh()->status)->toBe('available');
});

test('an unavailable car cannot be rented', function () {
    $user = User::factory()->create();
    $car = Car::create([
        'brand' => 'Busy Car',
        'year' => 2024,
        'number_plate' => 'BUSY001',
        'fuel_type' => 'petrol',
        'transmission' => 'automatic',
        'price_per_minute' => 3,
        'status' => 'busy',
    ]);

    $this->actingAs($user)
        ->get(route('rent.create', ['car_id' => $car->id]))
        ->assertRedirect(route('cars'))
        ->assertSessionHasErrors('car');
});

test('payment form keeps safe input after a validation error', function () {
    $user = User::factory()->create();
    $car = Car::create([
        'brand' => 'Input Test Car',
        'year' => 2024,
        'number_plate' => 'INPUT01',
        'fuel_type' => 'electric',
        'transmission' => 'automatic',
        'price_per_minute' => 2,
        'status' => 'available',
    ]);

    $rentalData = [
        'car_id' => $car->id,
        'branch' => 'airport',
        'start_time' => '2026-07-01 10:00',
        'end_time' => '2026-07-01 11:00',
    ];

    $this->actingAs($user)
        ->post(route('payment.preview'), $rentalData)
        ->assertRedirect(route('payment.show'));

    $this->from(route('payment.show'))->post(route('payment.process'), [
        ...$rentalData,
        'card_name' => 'Saved Name',
        'card_number' => '4242 4242 4242 4242',
        'expiry' => '',
        'cvc' => '123',
    ])->assertRedirect(route('payment.show'))
        ->assertSessionHasErrors('expiry')
        ->assertSessionHasInput('card_name', 'Saved Name');

    $this->get(route('payment.show'))
        ->assertOk()
        ->assertSee('value="Saved Name"', false);
});
