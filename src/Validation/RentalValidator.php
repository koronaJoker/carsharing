<?php

namespace App\Validation;

use DateTime;
use InvalidArgumentException;

class RentalValidator
{
    public static function rent(array $data): array
    {
        $carId = (int)($data['car_id'] ?? 0);
        $address = trim($data['address'] ?? '');
        $startTime = trim($data['start_time'] ?? '');
        $endTime = trim($data['end_time'] ?? '');

        if ($carId <= 0) {
            throw new InvalidArgumentException('Выберите автомобиль.');
        }

        if (mb_strlen($address) < 5) {
            throw new InvalidArgumentException('Укажите адрес подачи автомобиля.');
        }

        if ($startTime === '' || $endTime === '') {
            throw new InvalidArgumentException('Укажите дату и время начала и окончания аренды.');
        }

        $start = new DateTime($startTime);
        $end = new DateTime($endTime);

        if ($end <= $start) {
            throw new InvalidArgumentException('Время окончания должно быть позже времени начала.');
        }

        return [
            'car_id' => $carId,
            'address' => $address,
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $end->format('Y-m-d H:i:s'),
        ];
    }
}
