<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayPaymentRequest;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

    public function index(Request $request)
    {
        $user = $request->user();

        $payments = Payment::query()
            ->whereHas('ride', fn($q) => $q->where('user_id', $user->id))
            ->with(['ride:id,end_address,completed_at'])
            ->latest()
            ->paginate(20);

        return response()->json($payments);
    }

    public function confirmCashPayment(Payment $payment)
    {
        $ride = $payment->ride;
        $user = auth()->user();

        if (!$user->driver || $ride->driver_id !== $user->driver->id) {
            return response()->json(['message' => 'You are not authorized to confirm this payment'], 403);
        }

        if ($payment->method !== 'cash' || $payment->status !== PaymentStatus::Pending->value) {
            return response()->json(['message' => 'Payment is not pending or not cash'], 400);
        }

        $payment->status = PaymentStatus::Paid->value;
        $payment->paid_at = now();
        $payment->save();

        return response()->json(['message' => 'Payment confirmed successfully', 'payment' => $payment], 200);
    }

    public function reportUnpaidCash(Request $request, Payment $payment)
    {
        $ride = $payment->ride;
        $user = $request->user();

        if (!$ride || !$user->driver || (int)$ride->driver_id !== (int)$user->driver->id) {
            return response()->json(['message' => 'You are not authorized to report this payment'], 403);
        }

        if ($payment->method !== 'cash') {
            return response()->json(['message' => 'Only cash payments can be reported here'], 400);
        }

        $validated = $request->validate([
            'recipient_email' => ['required', 'email'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $ride->loadMissing(['user:id,name,email', 'driver.user:id,name,email']);

        $subject = 'GoRide unpaid cash report - Payment #' . $payment->id;
        $note = trim((string)($validated['note'] ?? ''));

        $body = implode("\n", [
            'An unpaid cash ride report was submitted.',
            '',
            'Payment ID: ' . $payment->id,
            'Ride ID: ' . $ride->id,
            'Amount: ' . (string)$payment->amount,
            'Method: ' . (string)$payment->method,
            'Status: ' . (string)$payment->status,
            'Passenger: ' . (($ride->user->name ?? 'N/A') . ' <' . ($ride->user->email ?? 'N/A') . '>'),
            'Driver: ' . (($ride->driver->user->name ?? 'N/A') . ' <' . ($ride->driver->user->email ?? 'N/A') . '>'),
            'From: ' . (string)($ride->start_address ?? ''),
            'To: ' . (string)($ride->end_address ?? ''),
            'Completed At: ' . (string)($ride->completed_at ?? 'N/A'),
            '',
            'Driver Note:',
            $note !== '' ? $note : '(no additional note)',
        ]);

        try {
            Mail::raw($body, function ($message) use ($validated, $subject) {
                $message->to($validated['recipient_email'])->subject($subject);
            });
        } catch (\Throwable $e) {
            $payload = ['message' => 'Failed to send report email'];
            if (config('app.debug')) {
                $payload['error'] = $e->getMessage();
            }
            return response()->json($payload, 500);
        }

        return response()->json([
            'message' => 'Unpaid cash report sent successfully',
            'sent_to' => $validated['recipient_email'],
            'payment' => $payment,
        ], 200);
    }
}
