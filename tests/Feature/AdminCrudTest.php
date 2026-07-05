<?php

use App\Models\Car;
use App\Models\Fine;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;

test('admin can manage records from all supported tables', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    foreach (['users', 'cars', 'rentals', 'payments', 'fines'] as $resource) {
        $this->get(route('admin.records.index', $resource))->assertOk();
        $this->get(route('admin.records.create', $resource))->assertOk();
    }

    $this->post(route('admin.records.store', 'users'), [
        'name' => 'Managed User',
        'email' => 'managed@example.com',
        'phone' => '+37360111111',
        'idnp' => '2000000000011',
        'driver_license' => 'MD111111',
        'role' => 'user',
        'email_verified_at' => '2026-06-28 12:00',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();
    $user = User::where('email', 'managed@example.com')->sole();

    $this->post(route('admin.records.store', 'cars'), [
        'brand' => 'Managed Car',
        'year' => 2025,
        'number_plate' => 'ADMIN01',
        'fuel_type' => 'hybrid',
        'transmission' => 'automatic',
        'price_per_minute' => 4.25,
        'status' => 'available',
        'image_url' => 'car-placeholder.webp',
    ])->assertRedirect();
    $car = Car::where('number_plate', 'ADMIN01')->sole();

    $this->post(route('admin.records.store', 'rentals'), [
        'user_id' => $user->id,
        'car_id' => $car->id,
        'start_time' => '2026-07-01 10:00',
        'end_time' => '2026-07-01 12:00',
        'total_cost' => 510,
        'status' => 'active',
    ])->assertRedirect();
    $rental = Rental::where('user_id', $user->id)->sole();
    expect($car->fresh()->status)->toBe('busy');

    $this->post(route('admin.records.store', 'payments'), [
        'rental_id' => $rental->id,
        'amount' => 510,
        'payment_method' => 'card',
        'payment_status' => 'paid',
        'paid_at' => '2026-07-01 10:00',
    ])->assertRedirect();
    $payment = Payment::where('rental_id', $rental->id)->sole();

    $this->post(route('admin.records.store', 'fines'), [
        'rental_id' => $rental->id,
        'title' => 'Нарушение парковки',
        'description' => 'Автомобиль оставлен в запрещённой зоне.',
        'amount' => 250,
        'rating_penalty' => 1.5,
        'status' => 'pending',
    ])->assertRedirect();
    $fine = Fine::where('rental_id', $rental->id)->sole();

    foreach ([
        'users' => $user,
        'cars' => $car,
        'rentals' => $rental,
        'payments' => $payment,
        'fines' => $fine,
    ] as $resource => $record) {
        $this->get(route('admin.records.show', [$resource, $record]))->assertOk();
        $this->get(route('admin.records.edit', [$resource, $record]))->assertOk();
    }

    $this->put(route('admin.records.update', ['cars', $car]), [
        'brand' => 'Updated Managed Car',
        'year' => 2025,
        'number_plate' => 'ADMIN01',
        'fuel_type' => 'hybrid',
        'transmission' => 'automatic',
        'price_per_minute' => 4.50,
        'status' => 'busy',
        'image_url' => 'car-placeholder.webp',
    ])->assertRedirect();

    expect($car->fresh()->brand)->toBe('Updated Managed Car');

    $this->delete(route('admin.records.destroy', ['fines', $fine]))->assertRedirect();
    $this->assertDatabaseMissing('fines', ['id' => $fine->id]);
});

test('admin form keeps previous input after validation error', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->from(route('admin.records.create', 'cars'))
        ->post(route('admin.records.store', 'cars'), [
            'brand' => '',
            'year' => 2025,
            'number_plate' => 'SAVED01',
            'fuel_type' => 'petrol',
            'transmission' => 'manual',
            'price_per_minute' => 3,
            'status' => 'available',
        ])
        ->assertRedirect(route('admin.records.create', 'cars'))
        ->assertSessionHasErrors('brand')
        ->assertSessionHasInput('number_plate', 'SAVED01');

    $this->get(route('admin.records.create', 'cars'))
        ->assertOk()
        ->assertSee('value="SAVED01"', false);
});

test('regular user cannot access admin records', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.records.index', 'users'))
        ->assertForbidden();
});
