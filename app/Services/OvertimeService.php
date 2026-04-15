<?php

namespace App\Services;

use App\Models\OvertimeRequest;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class OvertimeService
{
    /**
     * Submit an overtime request.
     */
    public function submitRequest(User $user, array $data, $attachment = null): OvertimeRequest
    {
        $date = $data['date'];
        $startTime = Carbon::parse($date . ' ' . $data['start_time']);
        $endTime = Carbon::parse($date . ' ' . $data['end_time']);

        // Handle overnight overtime
        if ($endTime->lte($startTime)) {
            $endTime->addDay();
        }

        $durationMinutes = $startTime->diffInMinutes($endTime);

        // 1. Minimum duration (e.g., 30 mins)
        if ($durationMinutes < 30) {
            throw new Exception('Durasi lembur minimal 30 menit.');
        }

        // 2. Check for duplicate on same date
        $exists = OvertimeRequest::where('user_id', $user->id)
            ->where('date', $date)
            ->where('status', '!=', 'rejected')
            ->exists();

        if ($exists) {
            throw new Exception('Anda sudah mengajukan lembur untuk tanggal ini.');
        }

        // 3. Compensation rate
        $rates = [
            'biasa' => 1.5,
            'libur' => 2.0,
            'darurat' => 1.5,
        ];
        $rate = $rates[$data['type']] ?? 1.5;

        // 4. Handle attachment
        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store('overtime', 'public');
        }

        // 5. Store
        return OvertimeRequest::create([
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $durationMinutes,
            'type' => $data['type'],
            'location' => $data['location'] ?? 'kantor',
            'description' => $data['description'],
            'attachment' => $attachmentPath ? '/storage/' . $attachmentPath : null,
            'compensation_rate' => $rate,
            'status' => 'pending',
        ]);
    }
}
