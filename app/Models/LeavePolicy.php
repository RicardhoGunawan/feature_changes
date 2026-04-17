<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePolicy extends Model
{
    protected $fillable = [
        'leave_type_id', 'description', 'accrual_type', 'default_quota',
        'min_service_months', 'can_carry_forward', 'max_carry_forward_days',
        'carry_forward_expiry_months', 'requires_attachment', 'allow_half_day',
        'allow_proxy_submission', 'min_staff_requirement'
    ];

    protected $casts = [
        'can_carry_forward' => 'boolean',
        'requires_attachment' => 'boolean',
        'allow_half_day' => 'boolean',
        'allow_proxy_submission' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function tiers()
    {
        return $this->hasMany(LeaveTierPolicy::class)->orderBy('min_years_service', 'desc');
    }
}
