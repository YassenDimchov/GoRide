<?php

namespace App\Support;

class Fare
{
    public static function estimate(float $km, int $minutes): float
    {
        $baseFare = 1.80;
        $includedKm = 2.0;
        $includedMin = 5;
        $perKmAfterIncluded = 0.85;
        $perMinAfterIncluded = 0.18;
        $minFare = 3.50;

        $billableKm = max(0.0, $km - $includedKm);
        $billableMin = max(0, $minutes - $includedMin);

        $fare = $baseFare
            + ($billableKm * $perKmAfterIncluded)
            + ($billableMin * $perMinAfterIncluded);

        return round(max($minFare, $fare), 2);
    }
}
