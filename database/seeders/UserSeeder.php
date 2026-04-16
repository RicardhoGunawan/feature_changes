<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Role System (2 roles only):
     *  - administrator : full Admin Dashboard access
     *  - employee      : mobile app only
     *
     * Approval authority is determined by POSITION HIERARCHY, not role.
     */
    public function run(): void
    {
        // Get shift and location IDs
        $shiftId    = DB::table('shifts')->where('name', 'Shift Pagi')->value('id');
        $locationId = DB::table('office_locations')->where('name', 'Kantor Pusat')->value('id');

        // Ensure Spatie roles exist
        Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'employee',      'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'employee',      'guard_name' => 'web']);

        $users = [
            // ── ADMINISTRATOR ROLE (Has Admin Dashboard access) ──────────────────
            [
                'username'             => 'director',
                'name'                 => 'Ahmad Director',
                'email'                => 'director@absenkan.com',
                'employee_code'        => 'EMP-001',
                'position_name'        => 'Director',
                'department'           => 'Management',
                'role'                 => 'administrator',
                'remaining_leave'      => 14,
                'sick_leave_remaining' => 30,
            ],
            [
                'username'             => 'managerhr',
                'name'                 => 'Budi Manager HR',
                'email'                => 'managerhr@absenkan.com',
                'employee_code'        => 'EMP-002',
                'position_name'        => 'Manager HR',
                'department'           => 'HR',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],
            [
                'username'             => 'managerfinance',
                'name'                 => 'Citra Manager Finance',
                'email'                => 'managerfinance@absenkan.com',
                'employee_code'        => 'EMP-003',
                'position_name'        => 'Manager Finance',
                'department'           => 'Finance',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],
            [
                'username'             => 'managerops',
                'name'                 => 'Doni Manager Operasional',
                'email'                => 'managerops@absenkan.com',
                'employee_code'        => 'EMP-004',
                'position_name'        => 'Manager Operasional',
                'department'           => 'Operasional',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],

            // ── EMPLOYEE ROLE (Mobile only, but can approve via position) ─────────
            [
                'username'             => 'spvhr',
                'name'                 => 'Eka Supervisor HR',
                'email'                => 'spvhr@absenkan.com',
                'employee_code'        => 'EMP-005',
                'position_name'        => 'Supervisor HR',
                'department'           => 'HR',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],
            [
                'username'             => 'spvfinance',
                'name'                 => 'Fajar Supervisor Finance',
                'email'                => 'spvfinance@absenkan.com',
                'employee_code'        => 'EMP-006',
                'position_name'        => 'Supervisor Finance',
                'department'           => 'Finance',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],
            [
                'username'             => 'spvops',
                'name'                 => 'Gita Supervisor Operasional',
                'email'                => 'spvops@absenkan.com',
                'employee_code'        => 'EMP-007',
                'position_name'        => 'Supervisor Operasional',
                'department'           => 'Operasional',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],
            [
                'username'             => 'staffhr',
                'name'                 => 'Hani Staff HR',
                'email'                => 'staffhr@absenkan.com',
                'employee_code'        => 'EMP-008',
                'position_name'        => 'Staff HR',
                'department'           => 'HR',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],
            [
                'username'             => 'stafffinance',
                'name'                 => 'Ivan Staff Finance',
                'email'                => 'stafffinance@absenkan.com',
                'employee_code'        => 'EMP-009',
                'position_name'        => 'Staff Finance',
                'department'           => 'Finance',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],
            [
                'username'             => 'staffops',
                'name'                 => 'Julia Staff Operasional',
                'email'                => 'staffops@absenkan.com',
                'employee_code'        => 'EMP-010',
                'position_name'        => 'Staff Operasional',
                'department'           => 'Operasional',
                'role'                 => 'employee',
                'remaining_leave'      => 12,
                'sick_leave_remaining' => 30,
            ],
        ];

        foreach ($users as $data) {
            $position = Position::where('name', $data['position_name'])->first();

            $user = User::updateOrCreate(
                ['username' => $data['username']],
                [
                    'name'                 => $data['name'],
                    'email'                => $data['email'],
                    'password'             => Hash::make('password123'),
                    'employee_code'        => $data['employee_code'],
                    'position_id'          => $position?->id,
                    'department'           => $data['department'],
                    'role'                 => $data['role'],
                    'is_active'            => true,
                    'join_date'            => '2024-01-01',
                    'shift_id'             => $shiftId,
                    'location_id'          => $locationId,
                    'remaining_leave'      => $data['remaining_leave'],
                    'sick_leave_remaining' => $data['sick_leave_remaining'],
                    'leave_reset_year'     => 2026,
                ]
            );

            // Assign Spatie role
            $user->syncRoles([$data['role']]);

            $roleLabel = $data['role'] === 'administrator' ? '👑 Admin' : '📱 Employee';
            $this->command->info("✓ {$roleLabel} | [{$data['position_name']}] {$data['name']} → {$data['username']} / password123");
        }

        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📋 Approval Flow (berdasarkan hierarki jabatan):');
        $this->command->info('   Staff → diapprove oleh → Supervisor');
        $this->command->info('   Supervisor → diapprove oleh → Manager');
        $this->command->info('   Manager → diapprove oleh → Director');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
