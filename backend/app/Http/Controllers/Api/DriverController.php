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

        return response()->json([
            'driver' => $driver,
        ]);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'message' => 'Driver profile not found.',
            ], 404);
        }

        if (($user->role ?? null) !== 'driver') {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'vehicle_make'  => ['nullable', 'string', 'max:50'],
            'vehicle_model' => ['nullable', 'string', 'max:50'],
            'license_plate' => ['nullable', 'string', 'max:20'],
        ]);

        if (array_key_exists('license_plate', $validated) && $validated['license_plate'] !== null) {
            $validated['license_plate'] = strtoupper(preg_replace('/\s+/', '', $validated['license_plate']));
        }

        $driver->fill($validated);
        $driver->save();

        return response()->json([
            'driver' => $driver,
        ]);
    }
}
