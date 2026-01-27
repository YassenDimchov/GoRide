<?php

function profileStats(string $token): array
{
    $ridesRes = apiMyRides($token);

    $rides = [];
    if (is_array($ridesRes)) {
        if (isset($ridesRes['rides']) && is_array($ridesRes['rides'])) {
            $rides = $ridesRes['rides'];
        } elseif (isset($ridesRes['data']) && is_array($ridesRes['data'])) {
            $rides = $ridesRes['data'];
        } elseif (isset($ridesRes['data']['rides']) && is_array($ridesRes['data']['rides'])) {
            $rides = $ridesRes['data']['rides'];
        } elseif (isset($ridesRes['rides']['data']) && is_array($ridesRes['rides']['data'])) {
            $rides = $ridesRes['rides']['data'];
        }
    }

    $totalTrips = 0;
    if (is_array($rides)) {
        foreach ($rides as $r) {
            if (($r['status'] ?? null) === 'completed') {
                $totalTrips++;
            }
        }
    }

    $payRes = apiPayments($token);

    $payments = [];
    if (is_array($payRes)) {
        if (isset($payRes['payments']) && is_array($payRes['payments'])) {
            $payments = $payRes['payments'];
        } elseif (isset($payRes['data']) && is_array($payRes['data'])) {
            $payments = $payRes['data'];
        } elseif (isset($payRes['data']['payments']) && is_array($payRes['data']['payments'])) {
            $payments = $payRes['data']['payments'];
        } elseif (isset($payRes['payments']['data']) && is_array($payRes['payments']['data'])) {
            $payments = $payRes['payments']['data'];
        }
    }

    $totalSpent = 0.0;
    if (is_array($payments)) {
        foreach ($payments as $p) {
            if (($p['status'] ?? null) === 'paid') {
                $totalSpent += (float)($p['amount'] ?? 0);
            }
        }
    }

    $revRes = apiMyReviews($token);

    $reviews = [];
    if (is_array($revRes)) {
        if (isset($revRes['reviews']) && is_array($revRes['reviews'])) {
            $reviews = $revRes['reviews'];
        } elseif (isset($revRes['data']) && is_array($revRes['data'])) {
            $reviews = $revRes['data'];
        } elseif (isset($revRes['data']['reviews']) && is_array($revRes['data']['reviews'])) {
            $reviews = $revRes['data']['reviews'];
        } elseif (isset($revRes['reviews']['data']) && is_array($revRes['reviews']['data'])) {
            $reviews = $revRes['reviews']['data'];
        }
    }

    $avgRating = null;
    if (!empty($reviews)) {
        $sum = 0; $n = 0;
        foreach ($reviews as $r) {
            if (isset($r['rating']) && is_numeric($r['rating'])) {
                $sum += (float)$r['rating'];
                $n++;
            }
        }
        if ($n > 0) $avgRating = round($sum / $n, 1);
    }

    return [
        'totalTrips' => $totalTrips,
        'totalSpent' => $totalSpent,
        'avgRating'  => $avgRating,
    ];
}
