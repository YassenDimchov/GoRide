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
            'license_plate' => ['nullable', 'string', 'max:20'],
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
}
