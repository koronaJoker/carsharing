<?php

namespace Database\Seeders;

use App\Models\Car;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        $cars = [
            ['brand' => 'Dacia Logan', 'year' => 2022, 'number_plate' => 'KAA101', 'fuel_type' => 'petrol', 'transmission' => 'manual', 'price_per_minute' => 2.50, 'status' => 'available', 'image_url' => '/images/dacia-logan.webp'],
            ['brand' => 'Dacia Sandero', 'year' => 2023, 'number_plate' => 'KAA102', 'fuel_type' => 'petrol', 'transmission' => 'manual', 'price_per_minute' => 2.80, 'status' => 'available', 'image_url' => '/images/dacia-sandero.webp'],
            ['brand' => 'Renault Clio', 'year' => 2023, 'number_plate' => 'KAA103', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'price_per_minute' => 3.20, 'status' => 'available', 'image_url' => '/images/renault-clio.webp'],
            ['brand' => 'Nissan Leaf', 'year' => 2022, 'number_plate' => 'KAA104', 'fuel_type' => 'electric', 'transmission' => 'automatic', 'price_per_minute' => 3.50, 'status' => 'available', 'image_url' => '/images/nissan-leaf.webp'],
            ['brand' => 'Toyota Corolla', 'year' => 2024, 'number_plate' => 'KAA105', 'fuel_type' => 'hybrid', 'transmission' => 'automatic', 'price_per_minute' => 4.00, 'status' => 'busy', 'image_url' => '/images/toyota-corolla.webp'],
            ['brand' => 'Skoda Octavia', 'year' => 2023, 'number_plate' => 'KAA106', 'fuel_type' => 'diesel', 'transmission' => 'automatic', 'price_per_minute' => 4.50, 'status' => 'available', 'image_url' => '/images/skoda-octavia.webp'],
            ['brand' => 'BMW 1 Series', 'year' => 2024, 'number_plate' => 'KAA107', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'price_per_minute' => 6.50, 'status' => 'maintenance', 'image_url' => '/images/bmw-1-series.webp'],
            ['brand' => 'Kia Ceed', 'year' => 2023, 'number_plate' => 'KAA108', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'price_per_minute' => 3.80, 'status' => 'available', 'image_url' => '/images/kia-ceed.webp'],
        ];

        foreach ($cars as $car) {
            Car::updateOrCreate(
                ['number_plate' => $car['number_plate']],
                $car
            );
        }
    }
}
