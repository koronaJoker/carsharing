<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RentalSeeder extends Seeder
{
    public function run(): void
    {
        $rentals = [
            ['email' => 'andrei.popescu@example.com', 'plate' => 'KAA101', 'start' => '2026-06-02 09:00:00', 'end' => '2026-06-02 12:00:00', 'cost' => 450.00, 'status' => 'completed'],
            ['email' => 'elena.rusu@example.com', 'plate' => 'KAA103', 'start' => '2026-06-08 10:00:00', 'end' => '2026-06-08 14:00:00', 'cost' => 768.00, 'status' => 'completed'],
            ['email' => 'victor.munteanu@example.com', 'plate' => 'KAA104', 'start' => '2026-06-14 18:00:00', 'end' => '2026-06-14 20:00:00', 'cost' => 420.00, 'status' => 'completed'],
            ['email' => 'elena.rusu@example.com', 'plate' => 'KAA106', 'start' => '2026-06-21 08:00:00', 'end' => '2026-06-21 14:00:00', 'cost' => 1620.00, 'status' => 'completed'],
            ['email' => 'andrei.popescu@example.com', 'plate' => 'KAA105', 'start' => '2026-06-28 13:00:00', 'end' => '2026-06-28 18:00:00', 'cost' => 1200.00, 'status' => 'active'],
        ];

        DB::transaction(function () use ($rentals): void {
            foreach ($rentals as $data) {
                $user = User::where('email', $data['email'])->firstOrFail();
                $car = Car::where('number_plate', $data['plate'])->firstOrFail();

                $rental = Rental::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'car_id' => $car->id,
                        'start_time' => $data['start'],
                    ],
                    [
                        'end_time' => $data['end'],
                        'total_cost' => $data['cost'],
                        'status' => $data['status'],
                    ]
                );

                Payment::updateOrCreate(
                    ['rental_id' => $rental->id],
                    [
                        'amount' => $data['cost'],
                        'payment_method' => 'card',
                        'payment_status' => 'paid',
                        'paid_at' => $data['start'],
                    ]
                );
            }
        });
    }
}
