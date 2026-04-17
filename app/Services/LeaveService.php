<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;

class LeaveService
{
    /**
     * Count workdays between two dates (excluding weekends).
     */
    public function countWorkdays($startDate, $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $workdays = 0;

        while ($start->lte($end)) {
            if (!$start->isWeekend()) {
                $workdays++;
            }
            $start->addDay();
        }

        return $workdays;
    }

    /**
     * Submit a leave request with policy validation.
     */
    public function submitRequest(User $user, array $data, $attachment = null): LeaveRequest
    {
        $code = $data['leave_type_code'] ?? 'AL'; // Default to Annual Leave if not provided
        $leaveType = LeaveType::where('code', $code)->first();
        
        if (!$leaveType || !$leaveType->policy) {
            throw new Exception("Kebijakan cuti untuk kode [{$code}] tidak ditemukan.");
        }

        $policy = $leaveType->policy;
        $startDate = $data['start_date'];
        $endDate = $data['end_date'] ?? $startDate;
        $duration_type = $data['leave_duration_type'] ?? 'full_day';
        $session = $data['half_day_session'] ?? null;

        // Force same date for half day
        if ($duration_type === 'half_day') {
            $endDate = $startDate;
            if (!$policy->allow_half_day) {
                throw new Exception('Jenis cuti ini tidak mendukung pengambilan setengah hari.');
            }
        }

        // 1. Min Service / Probation Check (Requirement #4.2)
        $monthsService = $user->join_date ? Carbon::parse($user->join_date)->diffInMonths(now()) : 0;
        if ($monthsService < $policy->min_service_months) {
            throw new Exception("Masa kerja Anda belum mencukupi untuk mengambil jenis cuti ini (Min: {$policy->min_service_months} bulan).");
        }

        // 2. Attachment Check (Requirement #8)
        if ($policy->requires_attachment && !$attachment) {
            throw new Exception('Lampiran (dokumen pendukung) wajib diunggah untuk jenis cuti ini.');
        }

        // 3. Check for pending requests
        $pending = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending_spv', 'pending_hr', 'pending_approval'])
            ->exists();

        if ($pending) {
            throw new Exception('Anda masih memiliki pengajuan yang sedang menunggu persetujuan.');
        }

        // 4. Check for overlapping dates
        $overlap = LeaveRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected', 'rejected_spv', 'cancelled'])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();

        if ($overlap) {
            throw new Exception('Anda sudah memiliki pengajuan aktif di rentang tanggal tersebut.');
        }

        // 5. Count workdays
        $workdaysCount = LeaveRequest::calculateWorkDays($startDate, $endDate, $duration_type === 'half_day');
        
        if ($workdaysCount == 0) {
            throw new Exception('Rentang tanggal hanya berisi hari libur atau akhir pekan.');
        }

        // 6. Team Constraint Check (Requirement #9)
        $dept = $user->department;
        if ($dept && $dept->max_concurrent_leave > 0) {
            $overlappingSameDept = LeaveRequest::whereHas('user', function($q) use ($dept) {
                    $q->where('department_id', $dept->id);
                })
                ->whereNotIn('status', ['rejected', 'rejected_spv', 'cancelled'])
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->count();
            
            if ($overlappingSameDept >= $dept->max_concurrent_leave) {
                $msg = $dept->leave_policy_notes ?: "Batas maksimal karyawan cuti bersamaan di departemen {$dept->name} sudah tercapai ({$dept->max_concurrent_leave} orang).";
                throw new Exception($msg);
            }
        }

        // 7. Dynamic Quota Calculation (Requirement #4.1)
        $eligibleQuota = $this->calculateEligibleQuota($user, $policy);
        
        // Use remaining_leave for Annual Leave (AL)
        if ($code === 'AL' && $user->remaining_leave < $workdaysCount) {
            throw new Exception("Sisa cuti tahunan tidak mencukupi (Tersisa: {$user->remaining_leave} hari).");
        }
        
        // Note: For other types, we might use a separate quota tracker or shared pool.

        // 8. Handle attachment
        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store('leaves', 'public');
        }

        // 9. Determine Dynamic Workflow (Requirement #1)
        $workflow = $dept ? $dept->leave_workflow_id : null;
        $currentStep = 1; // Start at step 1
        
        // Initial status based on the first step of the workflow
        // Defaulting to pending_spv if no workflow defined
        $status = 'pending_spv';
        if ($workflow) {
            $firstStep = \App\Models\ApprovalStep::where('workflow_id', $workflow)->where('step_number', 1)->first();
            if ($firstStep) {
                $status = ($firstStep->approver_type === 'hr') ? 'pending_hr' : 'pending_spv';
            }
        }

        // 10. Create request
        return LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'leave_duration_type' => $duration_type,
            'half_day_session' => $session,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'work_days' => ceil($workdaysCount),
            'total_days' => $workdaysCount,
            'reason' => $data['reason'],
            'attachment' => $attachmentPath ? '/storage/' . $attachmentPath : null,
            'remaining_leave_at_req' => $user->remaining_leave,
            'sick_leave_at_req' => $user->sick_leave_remaining,
            'status' => $status,
            'current_step' => $currentStep,
        ]);
    }

    /**
     * Calculate how many days an employee is entitled to based on their service years.
     */
    public function calculateEligibleQuota(User $user, LeavePolicy $policy): int
    {
        if ($policy->accrual_type !== 'by_service_tier') {
            return $policy->default_quota;
        }

        $yearsService = $user->join_date ? Carbon::parse($user->join_date)->diffInYears(now()) : 0;
        
        // Find the matching tier (highest min_years that matches)
        $matchingTier = $policy->tiers()
            ->where('min_years_service', '<=', $yearsService)
            ->first();

        return $matchingTier ? $matchingTier->quota_days : $policy->default_quota;
    }
}
