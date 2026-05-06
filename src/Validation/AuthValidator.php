<?php

namespace App\Validation;

use InvalidArgumentException;

class AuthValidator
{
    public static function registration(array $data): array
    {
        $fullName = trim($data['full_name'] ?? '');
        $email = mb_strtolower(trim($data['email'] ?? ''));
        $phone = trim($data['phone'] ?? '');
        $idnp = trim($data['idnp'] ?? '');
        $license = strtoupper(trim($data['driver_license'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? '');

        if ($fullName === '' || mb_strlen($fullName) < 3) {
            throw new InvalidArgumentException('Укажите имя и фамилию, минимум 3 символа.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Введите корректный email.');
        }

        if (!preg_match('/^\+?[0-9]{8,15}$/', $phone)) {
            throw new InvalidArgumentException('Телефон должен содержать 8-15 цифр и может начинаться с +.');
        }

        if (!preg_match('/^[0-9]{13}$/', $idnp)) {
            throw new InvalidArgumentException('IDNP должен содержать ровно 13 цифр.');
        }

        if (!preg_match('/^[A-Z0-9-]{4,30}$/', $license)) {
            throw new InvalidArgumentException('Номер водительского удостоверения должен содержать 4-30 символов.');
        }

        self::strongPassword($password);

        if ($password !== $confirmPassword) {
            throw new InvalidArgumentException('Пароли не совпадают.');
        }

        return [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'idnp' => $idnp,
            'driver_license' => $license,
            'driver_rating' => 5.0,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ];
    }

    public static function login(array $data): array
    {
        $login = trim($data['login'] ?? '');
        $password = (string)($data['password'] ?? '');

        if ($login === '') {
            throw new InvalidArgumentException('Введите email или логин.');
        }

        if ($password === '') {
            throw new InvalidArgumentException('Введите пароль.');
        }

        return ['login' => $login, 'password' => $password];
    }

    private static function strongPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Пароль должен быть не короче 8 символов.');
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('Пароль должен содержать заглавную букву, строчную букву и цифру.');
        }
    }
}
