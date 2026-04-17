<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentConstraintSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Finance: Very strict (Max 1 person)
        Department::where('name', 'Finance')->update([
            'max_concurrent_leave' => 1,
            'leave_policy_notes' => 'Hanya 1 orang dari bagian Keuangan yang boleh cuti di hari yang sama demi kelancaran transaksi perusahaan.'
        ]);

        // 2. HR & Management: (Max 2 people)
        Department::whereIn('name', ['HR', 'Management'])->update([
            'max_concurrent_leave' => 2
        ]);

        // 3. Operasional: (Max 3 people)
        Department::where('name', 'Operasional')->update([
            'max_concurrent_leave' => 3
        ]);

        $this->command->info('✓ Department Team Constraints seeded successfully!');
    }
}
