<?php
namespace App\Model;

use Exception;

/**
 * Represents a validated car entity.
 */
class Car
{
    /**
     * Car brand name.
     */
    private string $brand;

    /**
     * Car model name.
     */
    private string $model;

    /**
     * Production year.
     */
    private int $year;

    /**
     * Vehicle plate number.
     */
    private string $plate_number;

    /**
     * Fuel type code.
     */
    private string $fuel_type;

    /**
     * Transmission type code.
     */
    private string $transmission;

    /**
     * Rental price per minute.
     */
    private float $price_per_minute;

    /**
     * Current availability status.
     */
    private string $status;

    /**
     * Creates a car entity and validates all fields.
     */
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

    /**
     * Returns the car brand.
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * Sets and validates the car brand.
     */
    public function setBrand(string $brand): void
    {
        if (trim($brand) === '') {
            throw new Exception("Brand cannot be empty.");
        }

        $this->brand = trim($brand);
    }

    /**
     * Returns the car model.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Sets and validates the car model.
     */
    public function setModel(string $model): void
    {
        if (trim($model) === '') {
            throw new Exception("Model cannot be empty.");
        }

        $this->model = trim($model);
    }

    /**
     * Returns the car production year.
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Sets and validates the car production year.
     */
    public function setYear(int $year): void
    {
        if ($year < 1990 || $year > (int) date('Y') + 1) {
            throw new Exception("Invalid car year.");
        }

        $this->year = $year;
    }

    /**
     * Returns the plate number.
     */
    public function getPlateNumber(): string
    {
        return $this->plate_number;
    }

    /**
     * Sets and normalizes the plate number.
     */
    public function setPlateNumber(string $plate_number): void
    {
        if (trim($plate_number) === '') {
            throw new Exception("Plate number cannot be empty.");
        }

        $this->plate_number = strtoupper(trim($plate_number));
    }

    /**
     * Returns the fuel type.
     */
    public function getFuelType(): string
    {
        return $this->fuel_type;
    }

    /**
     * Sets and validates the fuel type.
     */
    public function setFuelType(string $fuel_type): void
    {
        $allowed = ['petrol', 'diesel', 'electric', 'hybrid', 'gas'];

        if (!in_array($fuel_type, $allowed)) {
            throw new Exception("Invalid fuel type.");
        }

        $this->fuel_type = $fuel_type;
    }

    /**
     * Returns the transmission type.
     */
    public function getTransmission(): string
    {
        return $this->transmission;
    }

    /**
     * Sets and validates the transmission type.
     */
    public function setTransmission(string $transmission): void
    {
        $allowed = ['manual', 'automatic'];

        if (!in_array($transmission, $allowed)) {
            throw new Exception("Invalid transmission.");
        }

        $this->transmission = $transmission;
    }

    /**
     * Returns the rental price per minute.
     */
    public function getPricePerMinute(): float
    {
        return $this->price_per_minute;
    }

    /**
     * Sets and validates the rental price per minute.
     */
    public function setPricePerMinute(float $price_per_minute): void
    {
        if ($price_per_minute <= 0) {
            throw new Exception("Price per minute must be greater than 0.");
        }

        $this->price_per_minute = $price_per_minute;
    }

    /**
     * Returns the car status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Sets and validates the car status.
     */
    public function setStatus(string $status): void
    {
        $allowed = ['available', 'rented', 'maintenance', 'inactive'];

        if (!in_array($status, $allowed)) {
            throw new Exception("Invalid car status.");
        }

        $this->status = $status;
    }

    /**
     * Converts the entity to a database-ready array.
     *
     * @return array<string, mixed>
     */
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
