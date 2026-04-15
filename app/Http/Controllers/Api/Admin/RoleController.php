<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends BaseAdminController
{
    public function index(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $permissions = Permission::where('guard_name', 'web')->get()->map(function ($p) {
            return [
                'id' => $p->name,
                'permission_name' => $this->getPermissionLabel($p->name),
            ];
        });

        $roles = Role::where('guard_name', 'web')->get()->map(function ($r) {
            return [
                'role' => $r->name,
                'role_label' => strtoupper($r->name),
                'permissions' => $r->permissions->pluck('name'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'master_permissions' => $permissions,
                'roles_config' => $roles
            ]
        ]);
    }

    public function updatePermissions(Request $request)
    {
        $this->validatePermission($request, 'manage_roles');
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'array',
        ]);

        // Sync for both guards to keep them in parity
        foreach (['web', 'api'] as $guard) {
            $role = Role::where('name', $validated['role'])->where('guard_name', $guard)->first();
            if ($role) {
                $role->syncPermissions($validated['permissions'] ?? []);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Hak akses untuk role ' . strtoupper($validated['role']) . ' berhasil diperbarui'
        ]);
    }
}
