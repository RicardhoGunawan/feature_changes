<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'leave_duration_type',
        'half_day_session',
        'start_date',
        'end_date',
        'work_days',
        'total_days',
        'reason',
        'attachment',
        'remaining_leave_at_req',
        'sick_leave_at_req',
        'status',
        'spv_reviewed_at',
        'reviewed_at',
        'spv_reviewed_by',
        'reviewed_by',
        'spv_review_note',
        'review_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'spv_reviewed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'total_days' => 'decimal:1',
    ];

    /**
     * Calculate netto work days excluding weekends and holidays
     */
    public static function calculateWorkDays($startDate, $endDate, $isHalfDay = false)
    {
        if ($isHalfDay) return 0.5;

        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        
        $holidays = Holiday::pluck('date')->map(fn($d) => $d->format('Y-m-d'))->toArray();
        
        $days = 0;
        $curr = $start->copy();
        
        while ($curr->lte($end)) {
            // Check if weekend (Saturday or Sunday)
            if (!$curr->isWeekend()) {
                // Check if holiday
                if (!in_array($curr->toDateString(), $holidays)) {
                    $days++;
                }
            }
            $curr->addDay();
        }
        
        return $days;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spv_reviewed_by');
    }

    public function hr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
