<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRideRequest;
use Illuminate\Http\Request;
use App\Models\Ride;
use App\Enums\RideStatus;


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

    public function accept(Ride $ride) {}
    public function start(Ride $ride) {}
    public function complete(Ride $ride) {}
}
