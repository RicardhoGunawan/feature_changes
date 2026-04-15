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

        $user = $user->load(['shift', 'location', 'roles']);
        
        // Fail-safe: Get ALL permission names associated with all user's roles across all guards
        $roleNames = $user->roles->pluck('name')->toArray();
        $user->permissions = DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->whereIn('roles.name', $roleNames)
            ->distinct()
            ->pluck('permissions.name');

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
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
        $user = $request->user()->load(['shift', 'location', 'roles']);
        
        // Fail-safe: Get ALL permission names associated with all user's roles across all guards
        $roleNames = $user->roles->pluck('name')->toArray();
        $user->permissions = DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->whereIn('roles.name', $roleNames)
            ->distinct()
            ->pluck('permissions.name');

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
        
        $user = $user->load(['shift', 'location', 'roles']);
        
        // Fail-safe: Get ALL permission names associated with all user's roles across all guards
        $roleNames = $user->roles->pluck('name')->toArray();
        $user->permissions = DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->whereIn('roles.name', $roleNames)
            ->distinct()
            ->pluck('permissions.name');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }
}
