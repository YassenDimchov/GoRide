<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Models\Ride;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(CreateReviewRequest $request, Ride $ride)
    {
        $user = $request->user();

        if ((int)$ride->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Only the passenger can review this ride.'], 403);
        }

        if ($ride->status !== 'completed') {
            return response()->json(['message' => 'Ride must be completed to leave a review.'], 409);
        }

        if (!$ride->driver_id) {
            return response()->json(['message' => 'Ride has no driver; cannot review.'], 409);
        }

        if ($ride->review) {
            return response()->json([
                'message' => 'Review already exists for this ride.',
                'review' => $ride->review,
            ], 200);
        }

        $review = Review::create([
            'ride_id' => $ride->id,
            'user_id' => $user->id,
            'driver_id' => $ride->driver_id,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
        ]);

        return response()->json(['review' => $review], 201);
    }

    public function index()
    {
        $user = auth()->user();

        $reviews = Review::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return response()->json($reviews);
    }

    public function driverReviews(\App\Models\Driver $driver)
    {
        $reviews = Review::query()
            ->where('driver_id', $driver->id)
            ->latest()
            ->paginate(20);

        return response()->json($reviews);
    }
}
