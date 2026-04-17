<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LeaveType extends Model
{
    protected $fillable = ['name', 'code', 'is_active'];

    public function policy(): HasOne
    {
        return $this->hasOne(LeavePolicy::class);
    }
}
