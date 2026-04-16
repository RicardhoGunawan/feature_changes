<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Position;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserByPositionSeeder extends Seeder
{
    /**
     * Role hanya 2:
     * - administrator : akses penuh ke Admin Dashboard
     * - employee       : hanya akses mobile app
     *
     * Approval/wewenang ditentukan oleh POSISI (jabatan), bukan role.
     * Director & Manager HR → administrator (akses Admin Dashboard)
     * Supervisor HR & Staff HR → employee (hanya mobile)
     */
    public function run(): void
    {
        $mapping = [
            ['position' => 'Director',      'role' => 'administrator', 'username' => 'director',    'prefix' => 'DIR'],
            ['position' => 'Manager HR',    'role' => 'administrator', 'username' => 'managerhr',   'prefix' => 'MGR'],
            ['position' => 'Supervisor HR', 'role' => 'employee',      'username' => 'supervisorhr','prefix' => 'SPV'],
            ['position' => 'Staff HR',      'role' => 'employee',      'username' => 'staffhr',     'prefix' => 'STF'],
        ];

        foreach ($mapping as $map) {
            $position = Position::where('name', $map['position'])->first();

            if (!$position) {
                $this->command->warn("Position [{$map['position']}] not found. Skipping.");
                continue;
            }

            // Ensure role exists in Spatie
            Role::firstOrCreate(['name' => $map['role'], 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => $map['role'], 'guard_name' => 'api']);

            $email = "{$map['username']}@absenkan.com";

            $user = User::updateOrCreate(
                ['username' => $map['username']],
                [
                    'name'                 => "{$map['position']} User",
                    'email'                => $email,
                    'password'             => Hash::make('password123'),
                    'employee_code'        => $map['prefix'] . '-' . str_pad($position->id, 3, '0', STR_PAD_LEFT),
                    'position_id'          => $position->id,
                    'role'                 => $map['role'],
                    'is_active'            => true,
                    'join_date'            => now(),
                    'remaining_leave'      => 12,
                    'sick_leave_remaining' => 30,
                ]
            );

            // Assign Spatie Role
            $user->syncRoles([$map['role']]);

            $this->command->info("✓ [{$map['position']}] → role: {$map['role']} | login: {$map['username']} / password123");
        }
    }
}
