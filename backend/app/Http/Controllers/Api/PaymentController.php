<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayPaymentRequest;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

        $query = Payment::query()
            ->whereHas('ride', fn($q) => $q->where('user_id', $user->id))
            ->with(['ride:id,end_address,completed_at'])
            ->latest();

        $status = strtolower((string) $request->query('status', 'all'));
        if ($status === PaymentStatus::Paid->value) {
            $query->where('status', PaymentStatus::Paid->value);
        } elseif ($status === 'unpaid') {
            $query->where('status', '!=', PaymentStatus::Paid->value);
        } elseif (in_array($status, [PaymentStatus::Pending->value, PaymentStatus::Failed->value], true)) {
            $query->where('status', $status);
        }

        // No limit for the payments page use case.
        $payments = $query->get();

        return response()->json([
            'data' => $payments,
            'total' => $payments->count(),
        ]);
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

    public function createStripeCheckout(Request $request, Payment $payment)
    {
        $user = $request->user();
        $ride = $payment->ride;

        if (!$ride || (int)$ride->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($payment->method, ['online', 'card', 'stripe'], true)) {
            return response()->json(['message' => 'Only card/online payments can be paid with Stripe'], 400);
        }

        if ($payment->status === PaymentStatus::Paid->value) {
            return response()->json(['message' => 'Payment already paid', 'payment' => $payment], 200);
        }

        if ($payment->status !== PaymentStatus::Pending->value) {
            return response()->json(['message' => 'Only pending payments can be processed'], 409);
        }

        $secretKey = (string) config('services.stripe.secret_key', '');
        if ($secretKey === '') {
            return response()->json(['message' => 'Stripe is not configured'], 500);
        }

        $amountCents = (int) round(((float) $payment->amount) * 100);
        if ($amountCents <= 0) {
            return response()->json(['message' => 'Invalid payment amount'], 422);
        }

        $currency = strtolower((string) config('services.stripe.currency', 'eur'));
        $successUrlTemplate = (string) config(
            'services.stripe.checkout_success_url',
            'http://localhost/GoRide/frontend/payment.php?stripe_success=1&payment_id={PAYMENT_ID}&session_id={CHECKOUT_SESSION_ID}'
        );
        $cancelUrlTemplate = (string) config(
            'services.stripe.checkout_cancel_url',
            'http://localhost/GoRide/frontend/payment.php?stripe_cancel=1'
        );

        $successUrl = str_replace('{PAYMENT_ID}', (string) $payment->id, $successUrlTemplate);
        $cancelUrl = str_replace('{PAYMENT_ID}', (string) $payment->id, $cancelUrlTemplate);

        $response = Http::asForm()
            ->withToken($secretKey)
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => (string) ($user->email ?? ''),
                'metadata[payment_id]' => (string) $payment->id,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => $currency,
                'line_items[0][price_data][unit_amount]' => $amountCents,
                'line_items[0][price_data][product_data][name]' => 'GoRide trip payment #' . $payment->id,
            ]);

        if (!$response->ok()) {
            return response()->json([
                'message' => 'Stripe checkout creation failed',
                'error' => config('app.debug') ? $response->json() : null,
            ], 502);
        }

        return response()->json([
            'checkout_url' => $response->json('url'),
            'session_id' => $response->json('id'),
            'payment' => $payment,
        ], 200);
    }

    public function confirmStripeCheckout(Request $request, Payment $payment)
    {
        $user = $request->user();
        $ride = $payment->ride;

        if (!$ride || (int)$ride->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $sessionId = trim((string) $request->input('session_id', ''));
        if ($sessionId === '') {
            return response()->json(['message' => 'Missing session_id'], 422);
        }

        $secretKey = (string) config('services.stripe.secret_key', '');
        if ($secretKey === '') {
            return response()->json(['message' => 'Stripe is not configured'], 500);
        }

        $sessionRes = Http::withToken($secretKey)
            ->get('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));

        if (!$sessionRes->ok()) {
            return response()->json([
                'message' => 'Could not verify Stripe session',
                'error' => config('app.debug') ? $sessionRes->json() : null,
            ], 502);
        }

        $stripePaymentStatus = strtolower((string) $sessionRes->json('payment_status', ''));
        $metadataPaymentId = (string) ($sessionRes->json('metadata.payment_id') ?? '');

        if ($metadataPaymentId !== (string) $payment->id) {
            return response()->json(['message' => 'Stripe session does not match this payment'], 409);
        }

        if ($stripePaymentStatus !== 'paid') {
            return response()->json(['message' => 'Stripe session is not paid yet'], 409);
        }

        if ($payment->status !== PaymentStatus::Paid->value) {
            $payment->status = PaymentStatus::Paid->value;
            $payment->paid_at = now();
            $payment->save();
        }

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'payment' => $payment,
        ], 200);
    }
}
