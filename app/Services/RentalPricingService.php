<?php

namespace App\Services;

class RentalPricingService
{
    private const MINUTES_PER_DAY = 1440;

    private const DAILY_CAP_IN_BASE_MINUTES = 240;

    public function calculate(int $minutes, float $pricePerMinute): float
    {
        if ($minutes <= 0 || $pricePerMinute <= 0) {
            return 0.0;
        }

        $fullDays = intdiv($minutes, self::MINUTES_PER_DAY);
        $remainingMinutes = $minutes % self::MINUTES_PER_DAY;

        $total = $fullDays * $this->dailyCap($pricePerMinute);
        $total += $this->calculatePartialDay($remainingMinutes, $pricePerMinute);

        return round($total, 2);
    }

    private function calculatePartialDay(int $minutes, float $pricePerMinute): float
    {
        if ($minutes === 0) {
            return 0.0;
        }

        $firstHour = min($minutes, 60);
        $secondAndThirdHours = min(max($minutes - 60, 0), 120);
        $fourthToSixthHours = min(max($minutes - 180, 0), 180);
        $afterSixHours = max($minutes - 360, 0);

        $cost = $pricePerMinute * (
            $firstHour
            + ($secondAndThirdHours * 0.5)
            + ($fourthToSixthHours * 0.25)
            + ($afterSixHours * 0.1)
        );

        return min($cost, $this->dailyCap($pricePerMinute));
    }

    private function dailyCap(float $pricePerMinute): float
    {
        return self::DAILY_CAP_IN_BASE_MINUTES * $pricePerMinute;
    }
}
