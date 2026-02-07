<?php

namespace App\Support;

class Fare
{
    public static function estimate(float $km, int $minutes): float
    {
        $base = 2.50;
        $perKm = 1.20;
        $perMin = 0.25;
        $minFare = 5.00;

        $fare = $base + ($km * $perKm) + ($minutes * $perMin);
        return round(max($minFare, $fare), 2);
    }
}
