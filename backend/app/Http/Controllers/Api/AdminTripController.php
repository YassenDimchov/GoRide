<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Http\Request;

class AdminTripController extends Controller
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

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(20, max(5, (int) $request->query('per_page', 8)));
        $search = trim((string) $request->query('search', ''));

        $query = Ride::query()
            ->with(['user:id,name,email,phone', 'driver.user:id,name,email,phone', 'review:id,ride_id,rating,review_text,created_at'])
            ->orderByRaw('COALESCE(completed_at, created_at) DESC');

        if ($search !== '') {
            $normalizedId = preg_replace('/\D+/', '', $search);
            $query->where(function ($q) use ($search, $normalizedId) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', '%'.$search.'%');
                })->orWhereHas('driver.user', function ($dq) use ($search) {
                    $dq->where('name', 'like', '%'.$search.'%');
                });

                if ($normalizedId !== '') {
                    $q->orWhere('id', (int) $normalizedId);
                }
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $trips = collect($paginator->items())->map(function (Ride $r) {
            return [
                'id' => $r->id,
                'status' => (string) $r->status,
                'passenger_count' => max(1, (int) ($r->passenger_count ?? 1)),
                'fare' => $r->fare !== null ? round((float) $r->fare, 2) : null,
                'trip_distance_m' => $r->trip_distance_m !== null ? (int) $r->trip_distance_m : null,
                'trip_duration_s' => $r->trip_duration_s !== null ? (int) $r->trip_duration_s : null,
                'start_address' => $r->start_address,
                'end_address' => $r->end_address,
                'created_at' => optional($r->created_at)->toISOString(),
                'completed_at' => optional($r->completed_at)->toISOString(),
                'user' => [
                    'id' => $r->user?->id,
                    'name' => $r->user?->name,
                    'email' => $r->user?->email,
                    'phone' => $r->user?->phone,
                ],
                'driver' => [
                    'id' => $r->driver?->id,
                    'name' => $r->driver?->user?->name,
                    'email' => $r->driver?->user?->email,
                    'phone' => $r->driver?->user?->phone,
                ],
                'review' => $r->review ? [
                    'rating' => (int) $r->review->rating,
                    'review_text' => $r->review->review_text,
                    'created_at' => optional($r->review->created_at)->toISOString(),
                ] : null,
            ];
        })->values();

        $totalRevenue = (float) (Ride::where('status', 'completed')->sum('fare') ?? 0);
        $totalTrips = (int) Ride::count();
        $activeUsers = (int) User::where('suspended', false)->count();
        $avgTripValue = (float) (Ride::where('status', 'completed')->avg('fare') ?? 0);

        return response()->json([
            'stats' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_trips' => $totalTrips,
                'active_users' => $activeUsers,
                'avg_trip_value' => round($avgTripValue, 2),
            ],
            'trips' => $trips,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
