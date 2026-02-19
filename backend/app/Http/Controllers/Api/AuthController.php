<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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


}
