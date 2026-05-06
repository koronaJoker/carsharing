<?php
namespace App\Model;

use Exception;

class Car
{
    private string $brand;
    private string $model;
    private int $year;
    private string $plate_number;
    private string $fuel_type;
    private string $transmission;
    private float $price_per_minute;
    private string $status;

    public function __construct(
        string $brand,
        string $model,
        int $year,
        string $plate_number,
        string $fuel_type,
        string $transmission,
        float $price_per_minute,
        string $status = 'available'
    ) {
        $this->setBrand($brand);
        $this->setModel($model);
        $this->setYear($year);
        $this->setPlateNumber($plate_number);
        $this->setFuelType($fuel_type);
        $this->setTransmission($transmission);
        $this->setPricePerMinute($price_per_minute);
        $this->setStatus($status);
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        if (trim($brand) === '') {
            throw new Exception("Brand cannot be empty.");
        }

        $this->brand = trim($brand);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        if (trim($model) === '') {
            throw new Exception("Model cannot be empty.");
        }

        $this->model = trim($model);
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        if ($year < 1990 || $year > (int) date('Y') + 1) {
            throw new Exception("Invalid car year.");
        }

        $this->year = $year;
    }

    public function getPlateNumber(): string
    {
        return $this->plate_number;
    }

    public function setPlateNumber(string $plate_number): void
    {
        if (trim($plate_number) === '') {
            throw new Exception("Plate number cannot be empty.");
        }

        $this->plate_number = strtoupper(trim($plate_number));
    }

    public function getFuelType(): string
    {
        return $this->fuel_type;
    }

    public function setFuelType(string $fuel_type): void
    {
        $allowed = ['petrol', 'diesel', 'electric', 'hybrid', 'gas'];

        if (!in_array($fuel_type, $allowed)) {
            throw new Exception("Invalid fuel type.");
        }

        $this->fuel_type = $fuel_type;
    }

    public function getTransmission(): string
    {
        return $this->transmission;
    }

    public function setTransmission(string $transmission): void
    {
        $allowed = ['manual', 'automatic'];

        if (!in_array($transmission, $allowed)) {
            throw new Exception("Invalid transmission.");
        }

        $this->transmission = $transmission;
    }

    public function getPricePerMinute(): float
    {
        return $this->price_per_minute;
    }

    public function setPricePerMinute(float $price_per_minute): void
    {
        if ($price_per_minute <= 0) {
            throw new Exception("Price per minute must be greater than 0.");
        }

        $this->price_per_minute = $price_per_minute;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $allowed = ['available', 'rented', 'maintenance', 'inactive'];

        if (!in_array($status, $allowed)) {
            throw new Exception("Invalid car status.");
        }

        $this->status = $status;
    }

    public function toArray(): array
    {
        return [
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'plate_number' => $this->plate_number,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'price_per_minute' => $this->price_per_minute,
            'status' => $this->status,
        ];
    }
}
