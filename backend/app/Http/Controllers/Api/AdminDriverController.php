<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDriverController extends Controller
{
    private function ensureAdmin(Request $request): ?\Illuminate\Http\JsonResponse
    {
        if (($request->user()->role ?? null) !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        if ($forbidden = $this->ensureAdmin($request)) {
            return $forbidden;
        }

        $search = trim((string) $request->query('search', ''));

        $query = Driver::query()
            ->with('user:id,name,email,phone,role')
            ->withCount('rides')
            ->withAvg('reviews', 'rating')
            ->withSum([
                'rides as earnings_total' => function ($q) {
                    $q->where('status', 'completed');
                },
            ], 'fare')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_make', 'like', '%'.$search.'%')
                    ->orWhere('vehicle_model', 'like', '%'.$search.'%')
                    ->orWhere('vehicle_color', 'like', '%'.$search.'%')
                    ->orWhere('license_plate', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        $drivers = $query->get()->map(function (Driver $d) {
            return [
                'id' => $d->id,
                'user_id' => $d->user_id,
                'name' => $d->user?->name,
                'email' => $d->user?->email,
                'phone' => $d->user?->phone,
                'user_role' => $d->user?->role,
                'vehicle_make' => $d->vehicle_make,
                'vehicle_model' => $d->vehicle_model,
                'vehicle_color' => $d->vehicle_color,
                'license_plate' => $d->license_plate,
                'status' => $d->status,
                'rides_count' => (int) ($d->rides_count ?? 0),
                'average_rating' => $d->reviews_avg_rating !== null ? round((float) $d->reviews_avg_rating, 2) : null,
                'earnings_total' => round((float) ($d->earnings_total ?? 0), 2),
                'created_at' => optional($d->created_at)->toISOString(),
            ];
        })->values();

        $totalDrivers = Driver::count();
        $onlineNow = Driver::query()
            ->whereHas('user', function ($q) {
                $q->where('role', 'driver');
            })
            ->where(function ($q) {
                $q->where('status', 'busy')
                    ->orWhere(function ($sq) {
                        $sq->where('status', 'available')
                            ->whereNotNull('last_seen_at')
                            ->where('last_seen_at', '>=', now()->subMinutes(5));
                    });
            })
            ->count();
        $avgRating = (float) (DB::table('reviews')->avg('rating') ?? 0);
        $tripsToday = DB::table('rides')
            ->whereDate('completed_at', now()->toDateString())
            ->count();

        return response()->json([
            'stats' => [
                'total_drivers' => $totalDrivers,
                'online_now' => $onlineNow,
                'avg_rating' => round($avgRating, 2),
                'total_trips_today' => (int) $tripsToday,
            ],
            'drivers' => $drivers,
        ]);
    }
}
