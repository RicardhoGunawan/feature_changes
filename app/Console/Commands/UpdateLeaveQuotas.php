<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Carbon\Carbon;

class UpdateLeaveQuotas extends Command
{
    protected $signature = 'app:update-leave-quotas';
    protected $description = 'Update employee leave quotas based on join date and tier policies (including carry forward)';

    public function handle(LeaveService $leaveService)
    {
        $today = Carbon::today();
        $users = User::where('is_active', true)->get();
        $this->info("Processing leave quotas for {$users->count()} employees...");

        // We focus on Annual Leave (AL) for now
        $annualType = LeaveType::where('code', 'AL')->first();
        if (!$annualType || !$annualType->policy) {
            $this->error('Annual Leave policy not found. Skipping.');
            return;
        }

        $policy = $annualType->policy;

        foreach ($users as $user) {
            if (!$user->join_date) continue;

            $joinDate = Carbon::parse($user->join_date);
            
            // 1. Check for Anniversary (Day and Month match)
            if ($joinDate->day === $today->day && $joinDate->month === $today->month) {
                $this->info("Anniversary detected for: {$user->name}");
                
                // Calculate new tier quota
                $newQuota = $leaveService->calculateEligibleQuota($user, $policy);
                $oldRemaining = $user->remaining_leave ?? 0;
                
                // 2. Handle Carry Forward (Requirement #6)
                $carriedDays = 0;
                if ($policy->can_carry_forward) {
                    $carriedDays = min($oldRemaining, $policy->max_carry_forward_days);
                }

                // 3. Update User
                $user->annual_leave_quota = $newQuota;
                $user->remaining_leave = $newQuota + $carriedDays;
                $user->save();

                $this->info("Updated {$user->name}: New Quota={$newQuota}, Total Balance={$user->remaining_leave} (Carried={$carriedDays})");
            }
        }

        $this->info('Leave quota update process completed.');
    }
}
