<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function frontendGoogleCallbackUrl(): string
    {
        return rtrim((string) config('services.google.frontend_callback_url', 'http://localhost/GoRide/frontend/google_callback.php'), '/');
    }

    private function googleRedirectUri(): string
    {
        return (string) config('services.google.redirect_uri', rtrim(config('app.url'), '/') . '/api/auth/google/callback');
    }

    public function googleRedirect()
    {
        $clientId = (string) config('services.google.client_id', '');
        if ($clientId === '') {
            return response()->json(['message' => 'GOOGLE_CLIENT_ID is not configured'], 500);
        }

        $state = Str::random(40);
        Cache::put('google_oauth_state_' . $state, true, now()->addMinutes(10));

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->googleRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function googleCallback(Request $request)
    {
        $frontendCallback = $this->frontendGoogleCallbackUrl();
        $redirectWithError = function (string $message) use ($frontendCallback) {
            return redirect()->away($frontendCallback . '?error=' . urlencode($message));
        };

        $state = (string) $request->query('state', '');
        if ($state === '' || !Cache::pull('google_oauth_state_' . $state, false)) {
            return $redirectWithError('Invalid or expired Google auth state.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return $redirectWithError('Missing Google authorization code.');
        }

        $clientId = (string) config('services.google.client_id', '');
        $clientSecret = (string) config('services.google.client_secret', '');
        if ($clientId === '' || $clientSecret === '') {
            return $redirectWithError('Google OAuth credentials are not configured.');
        }

        $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->googleRedirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenRes->ok()) {
            return $redirectWithError('Google token exchange failed.');
        }

        $accessToken = (string) ($tokenRes->json('access_token') ?? '');
        if ($accessToken === '') {
            return $redirectWithError('Google access token missing.');
        }

        $profileRes = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
        if (!$profileRes->ok()) {
            return $redirectWithError('Failed to load Google profile.');
        }

        $email = strtolower(trim((string) ($profileRes->json('email') ?? '')));
        $name = trim((string) ($profileRes->json('name') ?? ''));
        $verified = (bool) ($profileRes->json('email_verified') ?? false);

        if ($email === '' || !$verified) {
            return $redirectWithError('Google account email is missing or not verified.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name !== '' ? $name : 'Google User',
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => 'user',
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return redirect()->away($frontendCallback . '?token=' . urlencode($token));
    }

    public function register(Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => 'user',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        $valid = false;

        if ($user) {
            try {
                $valid = Hash::check($data['password'], (string) $user->password);
            } catch (\RuntimeException $e) {
                $valid = false;
            }
        }

        if (!$valid) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function me(Request $request) {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function destroy(Request $request) {
        $user = $request->user();

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted']);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;
        $user->save();

        return response()->json([
            'user' => $user,
            'message' => 'Profile updated',
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'oldPassword' => 'required|string',
            'newPassword' => 'required|string|confirmed|min:8',
        ]);

        if (!Hash::check($validated['oldPassword'], $user->password)) {
            throw ValidationException::withMessages([
                'oldPassword' => ['The provided password does not match our records.'],
            ]);
        }

        $user->password = Hash::make($validated['newPassword']);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function logoutAll(Request $request)
    {
        $user = Auth::user();

        $user->tokens->each(function ($token) use ($request) {
            if ($token->id != $request->user()->currentAccessToken()->id) {
                $token->delete();
            }
        });

        return response()->json(['message' => 'Logged out from all other devices.']);
    }

    public function sessions(Request $request)
    {
        $user = $request->user();
        $currentId = $request->user()?->currentAccessToken()?->id;

        $sessions = $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'created_at', 'last_used_at'])
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'created_at' => optional($t->created_at)->toISOString(),
                'last_used_at' => optional($t->last_used_at)->toISOString(),
                'is_current' => $currentId !== null && (int)$t->id === (int)$currentId,
            ])
            ->values();

        return response()->json(['sessions' => $sessions]);
    }

    public function applyDriver(Request $request)
    {
        $user = $request->user();
        if (($user->role ?? 'user') !== 'user') {
            return response()->json(['message' => 'Only users can submit a driver application'], 403);
        }

        $data = $request->validate([
            'vehicle_make' => ['required', 'string', 'max:60'],
            'vehicle_model' => ['required', 'string', 'max:60'],
            'vehicle_color' => ['required', 'string', 'max:30'],
            'license_plate' => ['required', 'string', 'max:30'],
        ]);

        $adminEmail = (string) env('DRIVER_APPLICATION_EMAIL', env('MAIL_FROM_ADDRESS', ''));
        if ($adminEmail === '') {
            return response()->json(['message' => 'Driver application email is not configured'], 500);
        }

        $subject = 'GoRide driver application - User #' . $user->id;
        $body = implode("\n", [
            'A user requested to become a driver.',
            '',
            'User ID: ' . $user->id,
            'Name: ' . (string) $user->name,
            'Email: ' . (string) $user->email,
            'Phone: ' . (string) ($user->phone ?? 'N/A'),
            '',
            'Vehicle Make: ' . $data['vehicle_make'],
            'Vehicle Model: ' . $data['vehicle_model'],
            'Vehicle Color: ' . $data['vehicle_color'],
            'License Plate: ' . $data['license_plate'],
            '',
            'Requested At: ' . now()->toDateTimeString(),
        ]);

        try {
            Mail::raw($body, function ($message) use ($adminEmail, $subject) {
                $message->to($adminEmail)->subject($subject);
            });
        } catch (\Throwable $e) {
            $payload = ['message' => 'Failed to send driver application'];
            if (config('app.debug')) {
                $payload['error'] = $e->getMessage();
            }
            return response()->json($payload, 500);
        }

        return response()->json([
            'message' => 'Driver application sent successfully',
            'sent_to' => $adminEmail,
        ]);
    }


}
