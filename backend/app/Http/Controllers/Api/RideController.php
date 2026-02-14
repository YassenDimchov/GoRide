<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRideRequest;
use Illuminate\Http\Request;
use App\Models\Ride;
use App\Enums\RideStatus;
use Illuminate\Support\Facades\DB;
use App\Models\Driver;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use App\Support\Fare;

class RideController extends Controller
{

    private function autoOfflineIfStale(Driver $driver, int $minutes = 5): bool
    {
        $cutoff = now()->subMinutes($minutes);
        $isStale = ($driver->last_seen_at === null) || $driver->last_seen_at->lt($cutoff);

        if ($driver->status === 'available' && $isStale) {
            $driver->status = 'offline';
            $driver->save();
            return true;
        }

        return false;
    }

    private function touchDriver(Driver $driver): void
    {
        $driver->last_seen_at = now();
        $driver->save();
    }

    // Create a new ride request.
    public function store(CreateRideRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = auth()->id();
        $data['driver_id'] = null;
        $data['status'] = RideStatus::PENDING->value;

        $distanceM = isset($data['trip_distance_m']) ? (int) $data['trip_distance_m'] : null;
        $durationS = isset($data['trip_duration_s']) ? (int) $data['trip_duration_s'] : null;

        if ($distanceM !== null && $durationS !== null) {
            $km = $distanceM / 1000;
            $min = max(1, (int) round($durationS / 60));

            $data['trip_distance_m'] = $distanceM;
            $data['trip_duration_s'] = $durationS;
            $data['estimated_fare']  = Fare::estimate($km, $min);
        }

        $ride = Ride::create($data);

        return response()->json(['data' => $ride], 201);
    }


    //Show ride details (used for testing)
    public function show(Ride $ride) 
    {
        $user = auth()->user();

        $isPassenger = (int)$ride->user_id === (int)$user->id;
        $isDriver = $user->driver && (int)$ride->driver_id === (int)$user->driver->id;

        if (!$isPassenger && !$isDriver) 
        {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ride->load([
        'user',
        'driver.user',
        'payment',
        'review',
        ]);

        return response()->json([
            'data' => $ride
        ], 200);
    }

    public function accept(Ride $ride)
    {
        $driver = auth()->user()->driver;

        if (!$driver) 
        {
            return response()->json([
                'message' => 'Only drivers can accept rides.'
            ], 403);
        }

        return DB::transaction(function () use ($ride, $driver) 
        {
            $lockedRide = Ride::whereKey($ride->id)
                ->lockForUpdate()
                ->first();
            
            if ($lockedRide->user_id === auth()->id()) 
            {
                return response()->json([
                    'message' => 'You cannot accept your own ride.'
                ], 409);
            }
            
            $lockedDriver = Driver::whereKey($driver->id)
                ->lockForUpdate()
                ->first();

            if ($this->autoOfflineIfStale($lockedDriver, 5)) {
                return response()->json([
                    'message' => 'Driver is offline due to inactivity.',
                    'code' => 'AUTO_OFFLINE',
                ], 409);
            }

            if ($lockedDriver->status !== 'available') {
                return response()->json(['message' => 'Driver is not available.'], 409);
            }

            $this->touchDriver($lockedDriver);
            
            // Ride was already accepted.
            if ($lockedRide->status !== RideStatus::PENDING->value) {
                // Same driver trying to accept
                if ($lockedRide->driver_id === $driver->id) {
                    return response()->json([
                        'message' => 'Ride already accepted by you.',
                        'data' => $lockedRide,
                    ], 200);
                }

                return response()->json([
                    'message' => 'Ride is not available for acceptance.'
                ], 409);
            }

            if ($lockedRide->driver_id !== null && $lockedRide->driver_id !== $driver->id) {
                return response()->json([
                    'message' => 'Ride has already been accepted by another driver.'
                ], 409);
            }

            $lockedRide->driver_id = $driver->id;
            $lockedRide->status = RideStatus::ACCEPTED->value;
            $lockedRide->accepted_at = now();

            $lockedRide->save();

            $lockedDriver->status = 'busy';
            $lockedDriver->save();

            return response()->json([
                'data' => $lockedRide
            ], 200);
        });
    }


    public function start(Ride $ride) 
    {
        $driver = auth()->user()->driver;

        if (!$driver) 
        {
            return response()->json([
                'message' => 'Only drivers can start rides.'
            ], 403);
        }

        if ($ride->driver_id !== $driver->id) 
        {
            return response()->json([
                'message' => 'You are not assigned to this ride.'
            ], 403);
        }

        if ($ride->status !== RideStatus::ACCEPTED->value) 
        {
            return response()->json([
                'message' => 'Ride cannot be started in its current state.'
            ], 409);
        }   

        $pickupLat = $ride->start_lat;
        $pickupLng = $ride->start_lng;
        $driverLat = $driver->current_lat;
        $driverLng = $driver->current_lng;

        $distance = $this->haversineKm($driverLat, $driverLng, $pickupLat, $pickupLng);

        if ($distance > 0.3) {
            return response()->json([
                'message' => 'You are too far from the pickup location to start the ride.',
                'distance' => $distance
            ], 409);
        }

        $ride->status = RideStatus::ONGOING->value;
        $ride->started_at = now();
        $ride->save();

        return response()->json([
            'data' => $ride
        ], 200);
    }

    public function complete(Ride $ride)
    {
        $driver = auth()->user()->driver;

        if (!$driver) {
            return response()->json(['message' => 'Only drivers can complete rides.'], 403);
        }

        if ((int)$ride->driver_id !== (int)$driver->id) {
            return response()->json(['message' => 'You are not assigned to this ride.'], 403);
        }

        if ($ride->status === RideStatus::COMPLETED->value) {
            $ride->load(['payment']);
            return response()->json([
                'message' => 'Ride already completed.',
                'data' => $ride,
                'payment' => $ride->payment,
            ], 200);
        }

        if ($ride->status !== RideStatus::ONGOING->value) {
            return response()->json(['message' => 'Ride cannot be completed in its current state.'], 409);
        }

        $finalFare = $ride->estimated_fare;

        if ($finalFare === null) {

            $finalFare = 5.00;
        }

        return DB::transaction(function () use ($ride, $driver, $finalFare) {

            $lockedRide = Ride::whereKey($ride->id)->lockForUpdate()->first();

            if ($lockedRide->status === RideStatus::COMPLETED->value) {
                $lockedRide->load(['payment']);
                return response()->json([
                    'message' => 'Ride already completed.',
                    'data' => $lockedRide,
                    'payment' => $lockedRide->payment,
                ], 200);
            }

            if ($lockedRide->status !== RideStatus::ONGOING->value) {
                return response()->json(['message' => 'Ride cannot be completed in its current state.'], 409);
            }

            $lockedRide->fare = $finalFare;
            $lockedRide->status = RideStatus::COMPLETED->value;
            $lockedRide->completed_at = now();
            $lockedRide->save();

            $payment = $lockedRide->payment()->updateOrCreate(
                ['ride_id' => $lockedRide->id],
                [
                    'amount'  => $lockedRide->fare,
                    'method'  => 'cash',
                    'status'  => PaymentStatus::Pending->value,
                    'paid_at' => null,
                ]
            );

            Driver::whereKey($driver->id)->update([
                'status' => 'available',
            ]);

            return response()->json([
                'data' => $lockedRide,
                'payment' => $payment,
            ], 200);
        });
    }


    public function mine(Request $request)
    {
        $user = $request->user();

        $query = Ride::query()->where('user_id', $user->id);

        if ($status = $request->query('status')) 
        {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) 
        {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) 
        {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($request->boolean('with_payment')) $query->with('payment');
        if ($request->boolean('with_review')) $query->with('review');
        if ($request->boolean('with_driver')) $query->with('driver.user');

        $rides = $query->latest()->paginate(20);

        return response()->json($rides);
    }

    public function driverRides(Request $request)
    {
        $user = $request->user();
        $driver = $user->driver;

        if (!$driver) 
        {
            return response()->json(['message' => 'Only drivers can view driver rides.'], 403);
        }

        $query = Ride::query()->where('driver_id', $driver->id);

        if ($status = $request->query('status')) 
        {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) 
        {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) 
        {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($request->boolean('with_payment')) $query->with('payment');
        if ($request->boolean('with_review')) $query->with('review');
        if ($request->boolean('with_user')) $query->with('user');

        $rides = $query->latest()->paginate(20);

        return response()->json($rides);
    }

    public function available(Request $request)
    {
        $user = $request->user();
        $driver = $user->driver;

        if (!$driver) 
        {
            return response()->json(['message' => 'Only drivers can view available rides.'], 403);
        }

        if ($driver->current_lat === null || $driver->current_lng === null) 
        {
            return response()->json(['message' => 'Driver location is not set.'], 409);
        }

        if ($this->autoOfflineIfStale($driver, 5)) {
            return response()->json([
                'message' => 'You were set offline due to inactivity.',
                'code' => 'AUTO_OFFLINE',
            ], 409);
        }

        if ($driver->status !== 'available') {
            return response()->json(['message' => 'Driver is not available.'], 409);
        }

        $this->touchDriver($driver);

        $rides = Ride::query()
            ->where('status', RideStatus::PENDING->value)
            ->whereNull('driver_id')
            ->with('user')
            ->get();

        $maxKm = 10.0;
        $maxWaitMin = 60.0;

        $scored = $rides->map(function ($ride) use ($driver, $maxKm, $maxWaitMin) 
        {

            $distanceKm = $this->haversineKm(
                (float) $driver->current_lat,
                (float) $driver->current_lng,
                (float) $ride->start_lat,
                (float) $ride->start_lng
            );

            $normDist = min($distanceKm / $maxKm, 1.0);

            $waitMin = max(0, (int) $ride->created_at->diffInMinutes(now()));
            $normWait = 1.0 - min($waitMin / $maxWaitMin, 1.0);

            $score = (0.7 * $normDist) + (0.3 * $normWait);
            $tripKm = $ride->trip_distance_m
                ? ($ride->trip_distance_m / 1000)
                : $this->haversineKm(
                    (float) $ride->start_lat,
                    (float) $ride->start_lng,
                    (float) $ride->end_lat,
                    (float) $ride->end_lng
                );

            $estimatedFare = $ride->estimated_fare;

            if ($estimatedFare === null) {
                $tripMin = $ride->trip_duration_s ? max(1, (int) round($ride->trip_duration_s / 60)) : 1;
                $estimatedFare = \App\Support\Fare::estimate($tripKm, $tripMin);
            }

            $ride->match = [
                'distance_km' => round($distanceKm, 2),
                'wait_min' => $waitMin,
                'trip_km' => round($tripKm, 2),
                'estimated_fare' => round((float)$estimatedFare, 2),
                'score' => round($score, 4),
            ];

            return $ride;
        })
        ->sortBy(fn($r) => $r->match['score'])
        ->values();

        return response()->json(['data' => $scored], 200);
    }



    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLon / 2) ** 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth * $c;
    }

    public function cancel(Ride $ride)
    {
        $user = auth()->user();

        if ((int)$ride->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (in_array($ride->status, [RideStatus::COMPLETED->value, RideStatus::CANCELLED->value], true)) {
            return response()->json(['message' => 'Ride already finished.'], 409);
        }

        if ($ride->status === RideStatus::ONGOING->value) {
            return response()->json(['message' => 'Cannot cancel an ongoing ride.'], 409);
        }

        return DB::transaction(function () use ($ride) {
            $lockedRide = Ride::whereKey($ride->id)->lockForUpdate()->first();

            if (in_array($lockedRide->status, [RideStatus::COMPLETED->value, RideStatus::CANCELLED->value], true)) {
                return response()->json(['message' => 'Ride already finished.'], 409);
            }

            if ($lockedRide->driver_id !== null) {
                Driver::whereKey($lockedRide->driver_id)->update(['status' => 'available']);
            }

            $lockedRide->status = RideStatus::CANCELLED->value;
            $lockedRide->save();

            return response()->json(['data' => $lockedRide], 200);
        });
    }

    public function driverActive()
    {
        $driver = auth()->user()->driver;
        if (!$driver) {
            return response()->json(['message' => 'Only drivers.'], 403);
        }

        $ride = Ride::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', [RideStatus::ACCEPTED->value, RideStatus::ONGOING->value])
            ->latest('accepted_at')
            ->with(['user'])
            ->first();

        return response()->json(['data' => $ride], 200);
    }




}
