<?php

namespace App\Validation;

use InvalidArgumentException;

class PaymentValidator
{
    public static function card(array $data): array
    {
        $method = $data['payment_method'] ?? 'card';
        $cardNumber = preg_replace('/\s+/', '', $data['card_number'] ?? '');
        $cardName = trim($data['card_name'] ?? '');
        $expires = trim($data['expires'] ?? '');
        $cvv = trim($data['cvv'] ?? '');

        if (!in_array($method, ['card', 'online'], true)) {
            throw new InvalidArgumentException('Выберите доступный способ оплаты.');
        }

        if (!preg_match('/^[0-9]{16}$/', $cardNumber)) {
            throw new InvalidArgumentException('Номер карты должен содержать 16 цифр.');
        }

        if (mb_strlen($cardName) < 3) {
            throw new InvalidArgumentException('Укажите имя держателя карты.');
        }

        if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expires)) {
            throw new InvalidArgumentException('Срок действия карты укажите в формате MM/YY.');
        }

        if (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
            throw new InvalidArgumentException('CVV должен содержать 3 или 4 цифры.');
        }

        return [
            'payment_method' => $method,
            'card_number' => $cardNumber,
            'card_name' => $cardName,
            'expires' => $expires,
            'cvv' => $cvv,
        ];
    }
}
