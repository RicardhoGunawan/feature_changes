<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;
use App\Models\Holiday;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Shifts ─────────────────────────────────────────────────────────────
        $shifts = [
            ['name' => 'Shift Pagi',  'start_time' => '08:00:00', 'end_time' => '17:00:00', 'late_tolerance_minutes' => 15],
            ['name' => 'Shift Siang', 'start_time' => '13:00:00', 'end_time' => '22:00:00', 'late_tolerance_minutes' => 15],
            ['name' => 'Shift Malam', 'start_time' => '22:00:00', 'end_time' => '07:00:00', 'late_tolerance_minutes' => 15],
        ];

        foreach ($shifts as $shift) {
            DB::table('shifts')->updateOrInsert(
                ['name' => $shift['name']],
                array_merge($shift, ['created_at' => now(), 'updated_at' => now()])
            );
        }
        $this->command->info('✓ Shifts seeded');

        // ── 2. Office Locations ───────────────────────────────────────────────────
        $locations = [
            [
                'name'      => 'Kantor Pusat',
                'latitude'  => -6.200000,
                'longitude' => 106.816666,
                'radius'    => 100,
                'is_active' => true,
            ],
            [
                'name'      => 'Kantor Cabang Jakarta Selatan',
                'latitude'  => -6.261493,
                'longitude' => 106.810600,
                'radius'    => 150,
                'is_active' => true,
            ],
        ];

        foreach ($locations as $loc) {
            DB::table('office_locations')->updateOrInsert(
                ['name' => $loc['name']],
                array_merge($loc, ['created_at' => now(), 'updated_at' => now()])
            );
        }
        $this->command->info('✓ Office locations seeded');

        // ── 3. Position Hierarchy ─────────────────────────────────────────────────
        // Level 4 = Paling Atas (Director), Level 1 = Paling Bawah (Staff)
        // parent_id NULL = Pimpinan Tertinggi (bisa approve semua di bawahnya)
        
        // Top Level: Director
        $director = Position::updateOrCreate(
            ['name' => 'Director'],
            ['level' => 4, 'department' => 'Management', 'parent_id' => null]
        );

        // Level 3: Manager melapor ke Director
        $managerHR = Position::updateOrCreate(
            ['name' => 'Manager HR'],
            ['level' => 3, 'department' => 'HR', 'parent_id' => $director->id]
        );
        $managerFinance = Position::updateOrCreate(
            ['name' => 'Manager Finance'],
            ['level' => 3, 'department' => 'Finance', 'parent_id' => $director->id]
        );
        $managerOperasional = Position::updateOrCreate(
            ['name' => 'Manager Operasional'],
            ['level' => 3, 'department' => 'Operasional', 'parent_id' => $director->id]
        );

        // Level 2: Supervisor melapor ke Manager
        $spvHR = Position::updateOrCreate(
            ['name' => 'Supervisor HR'],
            ['level' => 2, 'department' => 'HR', 'parent_id' => $managerHR->id]
        );
        $spvFinance = Position::updateOrCreate(
            ['name' => 'Supervisor Finance'],
            ['level' => 2, 'department' => 'Finance', 'parent_id' => $managerFinance->id]
        );
        $spvOperasional = Position::updateOrCreate(
            ['name' => 'Supervisor Operasional'],
            ['level' => 2, 'department' => 'Operasional', 'parent_id' => $managerOperasional->id]
        );

        // Level 1: Staff melapor ke Supervisor
        $staffHR = Position::updateOrCreate(
            ['name' => 'Staff HR'],
            ['level' => 1, 'department' => 'HR', 'parent_id' => $spvHR->id]
        );
        $staffFinance = Position::updateOrCreate(
            ['name' => 'Staff Finance'],
            ['level' => 1, 'department' => 'Finance', 'parent_id' => $spvFinance->id]
        );
        $staffOperasional = Position::updateOrCreate(
            ['name' => 'Staff Operasional'],
            ['level' => 1, 'department' => 'Operasional', 'parent_id' => $spvOperasional->id]
        );

        $this->command->info('✓ Positions seeded (4-level hierarchy)');

        // ── 4. National Holidays 2026 ─────────────────────────────────────────────
        $holidays = [
            ['name' => 'Tahun Baru 2026',           'date' => '2026-01-01', 'type' => 'national'],
            ['name' => 'Isra Miraj',                 'date' => '2026-01-27', 'type' => 'national'],
            ['name' => 'Tahun Baru Imlek',           'date' => '2026-02-17', 'type' => 'national'],
            ['name' => 'Hari Raya Nyepi',            'date' => '2026-03-19', 'type' => 'national'],
            ['name' => 'Idul Fitri 1447 H (Hari 1)','date' => '2026-03-30', 'type' => 'national'],
            ['name' => 'Idul Fitri 1447 H (Hari 2)','date' => '2026-03-31', 'type' => 'national'],
            ['name' => 'Wafat Yesus Kristus',        'date' => '2026-04-03', 'type' => 'national'],
            ['name' => 'Hari Buruh Nasional',        'date' => '2026-05-01', 'type' => 'national'],
            ['name' => 'Kenaikan Yesus Kristus',     'date' => '2026-05-14', 'type' => 'national'],
            ['name' => 'Hari Raya Waisak',           'date' => '2026-05-23', 'type' => 'national'],
            ['name' => 'Hari Lahir Pancasila',       'date' => '2026-06-01', 'type' => 'national'],
            ['name' => 'Idul Adha 1447 H',           'date' => '2026-06-07', 'type' => 'national'],
            ['name' => 'Tahun Baru Hijriah',         'date' => '2026-06-26', 'type' => 'national'],
            ['name' => 'Hari Kemerdekaan RI',        'date' => '2026-08-17', 'type' => 'national'],
            ['name' => 'Maulid Nabi Muhammad SAW',   'date' => '2026-09-04', 'type' => 'national'],
            ['name' => 'Hari Natal',                 'date' => '2026-12-25', 'type' => 'national'],
            // Company holidays
            ['name' => 'Cuti Bersama Idul Fitri',   'date' => '2026-04-01', 'type' => 'company'],
            ['name' => 'Cuti Bersama Idul Fitri',   'date' => '2026-04-02', 'type' => 'company'],
            ['name' => 'Libur Akhir Tahun',          'date' => '2026-12-31', 'type' => 'company'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date']],
                $holiday
            );
        }
        $this->command->info('✓ Holidays 2026 seeded');
    }
}
