<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('profile', absolute: false));
});

test('admins are also redirected to the profile after login', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect(route('profile', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'password' => 'Неверный пароль.',
    ]);
});

test('a clear error is shown when the user does not exist', function () {
    $response = $this->from('/login')->post('/login', [
        'email' => 'missing@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response
        ->assertRedirect('/login')
        ->assertSessionHasErrors([
            'email' => 'Пользователь с такой электронной почтой не найден.',
        ]);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
