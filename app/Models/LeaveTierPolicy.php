<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveTierPolicy extends Model
{
    protected $fillable = ['leave_policy_id', 'min_years_service', 'quota_days'];

    public function policy()
    {
        return $this->belongsTo(LeavePolicy::class);
    }
}
