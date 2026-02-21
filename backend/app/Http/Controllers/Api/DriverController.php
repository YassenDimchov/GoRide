<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Driver;

class DriverController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'message' => 'Driver profile not found.',
            ], 404);
        }

        $cutoff = now()->subMinutes(5);

        $isStale = ($driver->last_seen_at === null) || $driver->last_seen_at->lt($cutoff);

        if ($driver->status === 'available' && $isStale) {
            $driver->status = 'offline';
        }

        $driver->last_seen_at = now();
        $driver->save();

        return response()->json([
            'driver' => $driver,
        ]);
    }

    public function profile(Request $request, $driver_id) {
        $user = $request->user();

        $driver = Driver::where('id', $driver_id)->first();

        if (!$driver) {
            return response()->json([
                'message' => 'Driver profile not found.',
            ], 404);
        }

        $driverName = $driver->user->name;

        $driverPhone = $driver->user->phone;

        $createdAt = $driver->created_at;
        $now = now();
        $activeTime = $createdAt->diff($now);
        $yearsActive = $activeTime->y;
        $monthsActive = $activeTime->m;
        $daysActive = $activeTime->days;

        $totalTrips = $driver->rides()->count();

        $rides = $driver->rides()->whereNotNull('accepted_at')->get();
        $totalResponseTime = 0;
        $responseCount = 0;

        foreach ($rides as $ride) {
            if ($ride->accepted_at) {
                $responseTime = abs($ride->accepted_at->diffInSeconds($ride->created_at));
                $totalResponseTime += $responseTime;
                $responseCount++;
            }
        }

        $averageResponseTime = $responseCount > 0 ? $totalResponseTime / $responseCount : 0;

        $ratingBreakdown = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ];

        foreach ($driver->reviews as $review) {
            $ratingBreakdown[$review->rating]++;
        }

        $totalRating = 0;
        $totalReviewCount = $driver->reviews()->count();
        foreach ($driver->reviews as $review) {
            $totalRating += $review->rating;
        }

        $averageReview = $totalReviewCount > 0 ? round($totalRating / $totalReviewCount, 2) : null;

        return response()->json([
            'driver' => [
                'name' => $driverName,
                'phone' => $driverPhone,
                'average_review' => $averageReview,
                'total_trips' => $totalTrips,
                'active_time' => [
                    'years' => $yearsActive,
                    'months' => $monthsActive,
                    'days' => $daysActive
                ],
                'average_response_time' => $averageResponseTime,
                'rating_breakdown' => $ratingBreakdown
            ]
        ]);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();

        if (($user->role ?? null) !== 'driver') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'message' => 'Driver profile not found.',
            ], 404);
        }

        $cutoff = now()->subMinutes(5);
        $isStale = ($driver->last_seen_at === null) || $driver->last_seen_at->lt($cutoff);

        if ($driver->status === 'available' && $isStale) {
            $driver->status = 'offline';
        }

        $validated = $request->validate([
            'vehicle_make'  => ['nullable', 'string', 'max:50'],
            'vehicle_model' => ['nullable', 'string', 'max:50'],
            'vehicle_color' => ['nullable', 'string', 'max:30'],
            'license_plate' => ['nullable', 'string', 'max:20'],
            'passenger_capacity' => ['nullable', 'integer', 'min:1', 'max:8'],
            'status' => ['nullable', 'string', Rule::in(['available', 'offline'])],
        ]);

        if (array_key_exists('license_plate', $validated) && $validated['license_plate'] !== null) {
            $validated['license_plate'] = strtoupper(preg_replace('/\s+/', '', $validated['license_plate']));
        }

        if (array_key_exists('status', $validated) && $driver->status === 'busy') {
            return response()->json([
                'message' => 'You are busy on a ride.',
            ], 409);
        }

        $driver->fill($validated);

        $driver->last_seen_at = now();
        
        $driver->save();

        return response()->json([
            'driver' => $driver,
        ]);
    }

    public function updateLocation(Request $request)
    {
        $user = $request->user();

        if (($user->role ?? null) !== 'driver') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        if (!$driver) {
            return response()->json(['message' => 'Driver profile not found.'], 404);
        }

        if ($driver->status === 'offline') {
            return response()->json(['message' => 'Driver is offline.'], 409);
        }

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $driver->current_lat = $data['lat'];
        $driver->current_lng = $data['lng'];

        $driver->last_seen_at = now();

        $driver->save();

        return response()->json([
            'ok' => true,
            'driver' => $driver,
        ]);
    }

}
