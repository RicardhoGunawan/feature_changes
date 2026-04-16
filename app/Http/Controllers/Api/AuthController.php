<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Http\Requests\Api\LoginRequest;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('username', $credentials['username'])
            ->orWhere('employee_code', $credentials['username'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->update(['last_login' => now()]);

        $user = $user->load(['shift', 'location', 'department', 'jobPosition']);
        
        // Simplified permissions based on role
        $user->permissions = $user->role === 'administrator' ? ['all'] : [];

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user' => [
                    'id'                   => $user->id,
                    'name'                 => $user->name,
                    'username'             => $user->username,
                    'email'                => $user->email,
                    'employee_id'          => $user->employee_code,
                    'employee_code'        => $user->employee_code,
                    'department'           => $user->department?->name ?? '-',
                    'phone'                => $user->phone,
                    'address'              => $user->address,
                    'profile_photo'        => $user->profile_photo,
                    'remaining_leave'      => $user->remaining_leave,
                    'sick_leave_remaining' => $user->sick_leave_remaining,
                    // ── New 2-role system ────────────────────────────────────
                    'role'         => $user->role,          // 'administrator' or 'employee'
                    'current_role' => $user->current_role,  // From Spatie roles
                    'is_admin'     => ($user->role === 'administrator'),
                    // ── Position info (for approval hierarchy display) ────────
                    'position'    => $user->jobPosition()->first()?->name ?? $user->position ?? null,
                    'position_id' => $user->position_id,
                    // ── Shift & Location ─────────────────────────────────────
                    'work_schedule' => $user->shift ? [
                        'shift_name'  => $user->shift->name,
                        'start_time'  => $user->shift->start_time,
                        'end_time'    => $user->shift->end_time,
                    ] : null,
                    'office_location' => $user->location ? [
                        'id'        => $user->location->id,
                        'name'      => $user->location->name,
                        'latitude'  => $user->location->latitude,
                        'longitude' => $user->location->longitude,
                        'radius'    => $user->location->radius,
                    ] : null,
                    'permissions' => $user->permissions,
                ],
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load(['shift', 'location', 'department', 'jobPosition']);
        $user->permissions = $user->role === 'administrator' ? ['all'] : [];

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        
        $user = $user->load(['shift', 'location', 'department', 'jobPosition']);
        $user->permissions = $user->role === 'administrator' ? ['all'] : [];

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }
}
