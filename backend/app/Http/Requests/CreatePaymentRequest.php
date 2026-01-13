<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'in:cash,card'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
