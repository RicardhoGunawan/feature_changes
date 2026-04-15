<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
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
     * Submit a leave request.
     */
    public function submitRequest(User $user, array $data, $attachment = null): LeaveRequest
    {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'] ?? $startDate;
        $type = $data['type'];
        $duration_type = $data['leave_duration_type'] ?? 'full_day';
        $session = $data['half_day_session'] ?? null;

        // Force same date for half day
        if ($duration_type === 'half_day') {
            $endDate = $startDate;
        }

        // 1. Check for pending requests
        $pending = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending_spv', 'pending_hr'])
            ->exists();

        if ($pending) {
            throw new Exception('Anda masih memiliki pengajuan yang pending.');
        }

        // 2. Check for overlapping dates
        $overlap = LeaveRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected', 'rejected_spv'])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();

        if ($overlap) {
            throw new Exception('Anda sudah memiliki pengajuan aktif di rentang tanggal tersebut.');
        }

        // 3. Count workdays (Holiday aware)
        $workdaysCount = LeaveRequest::calculateWorkDays($startDate, $endDate, $duration_type === 'half_day');
        
        if ($workdaysCount == 0) {
            throw new Exception('Rentang tanggal hanya berisi hari libur atau akhir pekan.');
        }

        // 4. Validate quota
        if ($type === 'cuti' && $user->remaining_leave < $workdaysCount) {
            throw new Exception("Sisa cuti tidak mencukupi (Tersisa: {$user->remaining_leave} hari).");
        }

        if ($type === 'sakit' && $user->sick_leave_remaining <= 0) {
            throw new Exception("Kuota izin sakit tahun ini sudah habis.");
        }

        // 5. Handle attachment
        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store('leaves', 'public');
        }

        // 6. Create request
        return LeaveRequest::create([
            'user_id' => $user->id,
            'type' => $type,
            'leave_duration_type' => $duration_type,
            'half_day_session' => $session,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'work_days' => ceil($workdaysCount), // For legacy integer field
            'total_days' => $workdaysCount,      // New decimal field
            'reason' => $data['reason'],
            'attachment' => $attachmentPath ? '/storage/' . $attachmentPath : null,
            'remaining_leave_at_req' => $user->remaining_leave,
            'sick_leave_at_req' => $user->sick_leave_remaining,
            'status' => 'pending_spv',
        ]);
    }
}
