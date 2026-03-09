<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentPreferenceController extends Controller
{
    public function getPreferredPaymentMethod()
    {
        $user = Auth::user();

        return response()->json(['preferred_payment' => $user->preferred_payment]);
    }

    public function updatePreferredPaymentMethod(Request $request)
    {
        $request->validate([
            'preferred_payment' => 'required|in:cash,online',
        ]);

        $user = Auth::user();
        $user->preferred_payment = $request->preferred_payment;
        $user->save();

        return response()->json(['message' => 'Payment method updated successfully']);
    }
}
