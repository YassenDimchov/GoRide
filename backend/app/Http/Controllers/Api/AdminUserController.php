<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    private function ensureAdmin(Request $request): ?\Illuminate\Http\JsonResponse
    {
        if (($request->user()->role ?? null) !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        if ($forbidden = $this->ensureAdmin($request)) {
            return $forbidden;
        }

        $search = trim((string) $request->query('search', ''));

        $query = User::query()
            ->withCount('rides')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $users = $query->get()->map(function (User $u) use ($request) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'suspended' => (bool) ($u->suspended ?? false),
                'rides_count' => (int) ($u->rides_count ?? 0),
                'created_at' => optional($u->created_at)->toISOString(),
                'is_current_admin' => (int) $u->id === (int) $request->user()->id,
            ];
        })->values();

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('suspended', false)->count(),
            'drivers' => User::where('role', 'driver')->count(),
            'suspended' => User::where('suspended', true)->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    public function setSuspended(Request $request, User $user)
    {
        if ($forbidden = $this->ensureAdmin($request)) {
            return $forbidden;
        }

        if (($user->role ?? null) === 'admin') {
            return response()->json(['message' => "Admin accounts can't be suspended."], 409);
        }

        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json(['message' => "You can't suspend your own account."], 409);
        }

        $data = $request->validate([
            'suspended' => ['required', 'boolean'],
        ]);

        $user->suspended = (bool) $data['suspended'];
        $user->save();

        if ($user->suspended) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $user->suspended ? 'User suspended.' : 'User unsuspended.',
            'user' => [
                'id' => $user->id,
                'suspended' => (bool) $user->suspended,
            ],
        ]);
    }

    public function setRole(Request $request, User $user)
    {
        if ($forbidden = $this->ensureAdmin($request)) {
            return $forbidden;
        }

        if (($user->role ?? null) === 'admin') {
            return response()->json(['message' => "Admin accounts can't be changed to another role."], 409);
        }

        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json(['message' => "You can't change your own role."], 409);
        }

        $data = $request->validate([
            'role' => ['required', 'string', 'in:user,driver'],
        ]);

        $nextRole = (string) $data['role'];
        $user->role = $nextRole;
        $user->save();

        if ($nextRole === 'driver') {
            Driver::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_make' => null,
                    'vehicle_model' => null,
                    'vehicle_color' => null,
                    'license_plate' => null,
                    'passenger_capacity' => 4,
                    'status' => 'offline',
                ]
            );
        } else {
            Driver::where('user_id', $user->id)->update([
                'status' => 'offline',
            ]);
        }

        return response()->json([
            'message' => $nextRole === 'driver' ? 'User promoted to driver.' : 'Driver changed to user.',
            'user' => [
                'id' => $user->id,
                'role' => $user->role,
            ],
        ]);
    }
}
