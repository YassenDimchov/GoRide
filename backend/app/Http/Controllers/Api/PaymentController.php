<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayPaymentRequest;
use App\Models\Payment;
use App\Enums\PaymentStatus;

class PaymentController extends Controller
{
    public function pay(PayPaymentRequest $request, Payment $payment)
    {
        $user = $request->user();
        $ride = $payment->ride;

        if (!$ride) {
            return response()->json(['message' => 'Ride not found for this payment'], 404);
        }

        if ((int)$ride->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($payment->status === PaymentStatus::Paid->value) {
            return response()->json(['message' => 'Payment already paid', 'payment' => $payment], 200);
        }

        if ($payment->status !== PaymentStatus::Pending->value) {
            return response()->json(['message' => 'Only pending payments can be paid'], 409);
        }

        $payment->status = PaymentStatus::Paid->value;
        $payment->paid_at = now();
        $payment->save();

        return response()->json(['payment' => $payment], 200);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        $payments = Payment::query()
            ->whereHas('ride', fn($q) => $q->where('user_id', $user->id))
            ->with(['ride:id,end_address,completed_at'])
            ->latest()
            ->paginate(20);

        return response()->json($payments);
    }
}
