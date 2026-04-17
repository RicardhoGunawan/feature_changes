<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use App\Models\LeavePolicy;

class MasterLeaveSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Annual Leave (Cuti Tahunan)
        $annual = LeaveType::updateOrCreate(['code' => 'AL'], ['name' => 'Cuti Tahunan']);
        $annualPolicy = LeavePolicy::updateOrCreate(['leave_type_id' => $annual->id], [
            'description' => 'Cuti tahunan reguler karyawan.',
            'accrual_type' => 'by_service_tier', // Updated according to requirement
            'default_quota' => 12,
            'min_service_months' => 6, // Probation rule #4.2
            'can_carry_forward' => true,
            'max_carry_forward_days' => 5,
            'carry_forward_expiry_months' => 6,
            'allow_half_day' => true,
        ]);

        // Add Tiers for Annual Leave (Requirement #4.1)
        $annualPolicy->tiers()->updateOrCreate(['min_years_service' => 3], ['quota_days' => 13]);
        $annualPolicy->tiers()->updateOrCreate(['min_years_service' => 4], ['quota_days' => 14]);
        $annualPolicy->tiers()->updateOrCreate(['min_years_service' => 5], ['quota_days' => 18]);

        // 2. Sick Leave (Cuti Sakit)
        $sick = LeaveType::updateOrCreate(['code' => 'SL'], ['name' => 'Cuti Sakit']);
        LeavePolicy::updateOrCreate(['leave_type_id' => $sick->id], [
            'description' => 'Cuti sakit dengan atau tanpa surat dokter.',
            'accrual_type' => 'full_at_start',
            'default_quota' => 30, // Example limit
            'requires_attachment' => true, // Requirement #8
            'allow_half_day' => false,
        ]);

        // 3. Haji Leave (Cuti Ibadah Haji)
        $haji = LeaveType::updateOrCreate(['code' => 'HL'], ['name' => 'Cuti Ibadah Haji']);
        LeavePolicy::updateOrCreate(['leave_type_id' => $haji->id], [
            'description' => 'Cuti ibadah haji (1x selama masa kerja).',
            'accrual_type' => 'full_at_start',
            'default_quota' => 40,
            'min_service_months' => 12,
            'allow_proxy_submission' => true, // Field employees might need this
        ]);

        // 4. Birthday Leave (Cuti Ulang Tahun)
        $bday = LeaveType::updateOrCreate(['code' => 'BDL'], ['name' => 'Cuti Ulang Tahun']);
        LeavePolicy::updateOrCreate(['leave_type_id' => $bday->id], [
            'description' => 'Cuti hadiah ulang tahun karyawan.',
            'accrual_type' => 'full_at_start',
            'default_quota' => 1,
            'allow_half_day' => false,
        ]);

        $this->command->info('✓ Master Leave Policies seeded successfully!');
    }
}
