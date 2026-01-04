<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRideRequest;
use Illuminate\Http\Request;
use App\Models\Ride;
use App\Enums\RideStatus;
use Illuminate\Support\Facades\DB;
use App\Models\Driver;

class RideController extends Controller
{
    // Create a new ride request.
    public function store(CreateRideRequest $request) 
    {
        $data = $request->validated();

        $data['user_id'] = auth()->id();
        $data['driver_id'] = null;
        $data['status'] = RideStatus::PENDING->value;

        $ride = Ride::create($data);
        return response()->json([
            'data' => $ride,
        ], 201);
    }

    //Show ride details (used for testing)
    public function show(Ride $ride) 
    {
        return response()->json([
            'data' => $ride,
        ]);
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

            
            // If driver is busy.
            if ($lockedDriver->status !== 'available') 
            {
                return response()->json([
                    'message' => 'Driver is not available.'
                ], 409);
            }
            
            // Ride was already accepted.
            if ($lockedRide->status !== RideStatus::PENDING->value) 
            {
                return response()->json([
                    'message' => 'Ride is not available for acceptance.'
                ], 409);
            }

            if ($lockedRide->driver_id !== null) 
            {
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
            return response()->json([
                'message' => 'Only drivers can complete rides.'
            ], 403);
        }

        if ($ride->driver_id !== $driver->id) 
        {
            return response()->json([
                'message' => 'You are not assigned to this ride.'
            ], 403);
        }

        if ($ride->status === RideStatus::COMPLETED->value) 
        {
            return response()->json([
                'message' => 'Ride has already been completed.'
            ], 409);
        } 

        if ($ride->status !== RideStatus::ONGOING->value) 
        {
            return response()->json([
                'message' => 'Ride cannot be completed in its current state.'
            ], 409);
        } 

        if (!$ride->started_at) {
            return response()->json([
                'message' => 'Ride has no started time; cannot calculate fare.'
            ], 500);
        }

        $minutes = max(1, $ride->started_at->diffInMinutes(now()));
        $ride->fare = $minutes * 1.0;

        $ride->status = RideStatus::COMPLETED->value;
        $ride->completed_at = now();
        $ride->save();

        Driver::whereKey($driver->id)->update(['status' => 'available']);

        return response()->json([
            'data' => $ride
        ], 200);
    }
}
