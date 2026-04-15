<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaseAdminController extends Controller
{
    /**
     * Centralized permission validation for all admin controllers.
     */
    protected function validatePermission(Request $request, string $permission)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            return true;
        }

        $roleNames = $user->roles->pluck('name')->toArray();
        $hasPerm = DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->whereIn('roles.name', $roleNames)
            ->where('permissions.name', $permission)
            ->exists();

        if (!$hasPerm) {
            abort(response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki izin ' . $permission
            ], 403));
        }

        return true;
    }

    /**
     * Shared labels for permissions.
     */
    protected function getPermissionLabel($name)
    {
        $labels = [
            'view_attendance' => 'Lihat Laporan Absensi',
            'manage_attendance' => 'Kelola/Input Absen Manual',
            'view_leave' => 'Lihat Pengajuan Izin & Cuti',
            'approve_leave_spv' => 'Persetujuan Izin (Level SPV)',
            'approve_leave_hr' => 'Persetujuan Izin (Level HR/Final)',
            'view_employee' => 'Lihat Data Karyawan',
            'manage_employee' => 'Tambah/Edit/Hapus Karyawan',
            'manage_location' => 'Atur Lokasi Kantor & Radius',
            'manage_schedule' => 'Atur Jadwal Kerja & Shift',
            'view_roles' => 'Lihat Role & Hak Akses',
            'manage_roles' => 'Kelola Hak Akses (RBAC)',
        ];

        return $labels[$name] ?? ucwords(str_replace('_', ' ', $name));
    }
}
