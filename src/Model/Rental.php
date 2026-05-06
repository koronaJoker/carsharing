<?php
namespace App\Classes;
use DateTime, Exception;
class Rental
{
    private int $client_id;
    private int $car_id;
    private DateTime $start_time;
    private ?DateTime $end_time;
    private float $total_cost;
    private string $status;

    public function __construct(
        int $client_id,
        int $car_id,
        string $start_time,
        ?string $end_time = null,
        float $total_cost = 0,
        string $status = 'active'
    ) {
        $this->setClientId($client_id);
        $this->setCarId($car_id);
        $this->setStartTime($start_time);
        $this->setEndTime($end_time);
        $this->setTotalCost($total_cost);
        $this->setStatus($status);
    }

    // --- CLIENT ---
    public function getClientId(): int
    {
        return $this->client_id;
    }

    public function setClientId(int $client_id): void
    {
        if ($client_id <= 0) {
            throw new Exception("Invalid client ID.");
        }
        $this->client_id = $client_id;
    }

    // --- CAR ---
    public function getCarId(): int
    {
        return $this->car_id;
    }

    public function setCarId(int $car_id): void
    {
        if ($car_id <= 0) {
            throw new Exception("Invalid car ID.");
        }
        $this->car_id = $car_id;
    }

    // --- START TIME ---
    public function getStartTime(): DateTime
    {
        return $this->start_time;
    }

    public function setStartTime(string $start_time): void
    {
        $this->start_time = new DateTime($start_time);
    }

    // --- END TIME ---
    public function getEndTime(): ?DateTime
    {
        return $this->end_time;
    }

    public function setEndTime(?string $end_time): void
    {
        $this->end_time = $end_time ? new DateTime($end_time) : null;
    }

    // --- COST ---
    public function getTotalCost(): float
    {
        return $this->total_cost;
    }

    public function setTotalCost(float $total_cost): void
    {
        if ($total_cost < 0) {
            throw new Exception("Cost cannot be negative.");
        }
        $this->total_cost = $total_cost;
    }

    // --- STATUS ---
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $allowed = ['active', 'completed', 'cancelled'];

        if (!in_array($status, $allowed)) {
            throw new Exception("Invalid rental status.");
        }

        $this->status = $status;
    }

    // --- BUSINESS LOGIC ---
    public function finishRental(string $end_time, float $price_per_minute): void
    {
        $this->setEndTime($end_time);

        $interval = $this->start_time->diff($this->end_time);
        $minutes = ($interval->days * 24 * 60) +
                   ($interval->h * 60) +
                   $interval->i;

        $this->total_cost = $minutes * $price_per_minute;
        $this->status = 'completed';
    }

    public function toArray(): array
    {
        return [
            'client_id' => $this->client_id,
            'car_id' => $this->car_id,
            'start_time' => $this->start_time->format('Y-m-d H:i:s'),
            'end_time' => $this->end_time?->format('Y-m-d H:i:s'),
            'total_cost' => $this->total_cost,
            'status' => $this->status,
        ];
    }
}
