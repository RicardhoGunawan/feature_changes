<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define Permissions
        $permissions = [
            ['name' => 'view_attendance', 'label' => 'Lihat Laporan Absensi'],
            ['name' => 'manage_attendance', 'label' => 'Kelola/Input Absen Manual'],
            ['name' => 'view_leave', 'label' => 'Lihat Pengajuan Izin & Cuti'],
            ['name' => 'approve_leave_spv', 'label' => 'Persetujuan Izin (Level SPV)'],
            ['name' => 'approve_leave_hr', 'label' => 'Persetujuan Izin (Level HR/Final)'],
            ['name' => 'view_employee', 'label' => 'Lihat Data Karyawan'],
            ['name' => 'manage_employee', 'label' => 'Tambah/Edit/Hapus Karyawan'],
            ['name' => 'manage_location', 'label' => 'Atur Lokasi Kantor & Radius'],
            ['name' => 'manage_schedule', 'label' => 'Atur Jadwal Kerja & Shift'],
            ['name' => 'view_roles', 'label' => 'Lihat Role & Hak Akses'],
            ['name' => 'manage_roles', 'label' => 'Kelola Hak Akses (RBAC)'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p['name'], 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $p['name'], 'guard_name' => 'api']);
        }

        // Setup for each guard
        foreach (['web', 'api'] as $guard) {
            // Assign all to admin automatically
            $admin = Role::where('name', 'admin')->where('guard_name', $guard)->first();
            if ($admin) {
                $admin->syncPermissions(Permission::where('guard_name', $guard)->get());
            }

            // Assign basic to SPV
            $spv = Role::where('name', 'spv')->where('guard_name', $guard)->first();
            if ($spv) {
                $spv->syncPermissions(
                    Permission::where('guard_name', $guard)
                        ->whereIn('name', [
                            'view_attendance',
                            'view_leave',
                            'approve_leave_spv',
                            'view_employee'
                        ])->get()
                );
            }
        }
    }
}
