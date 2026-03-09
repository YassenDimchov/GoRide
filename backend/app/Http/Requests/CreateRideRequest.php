<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth is handled by API middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'start_lat' => ['required', 'numeric', 'between:-90,90'],
            'start_lng' => ['required', 'numeric', 'between:-180,180'],
            'end_lat' => ['required', 'numeric', 'between:-90,90'],
            'end_lng' => ['required', 'numeric', 'between:-180,180'],

            'start_address' => ['required', 'string', 'max:255'],
            'end_address' => ['required', 'string', 'max:255'],
            'passenger_count' => ['required', 'integer', 'min:1', 'max:8'],
            'trip_distance_m' => ['nullable', 'numeric', 'min:0'],
            'trip_duration_s' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
