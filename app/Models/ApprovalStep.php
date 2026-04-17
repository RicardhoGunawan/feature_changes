<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    protected $fillable = [
        'workflow_id', 'step_number', 'approver_type', 
        'specific_user_id', 'is_final'
    ];

    protected $casts = [
        'is_final' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }
}
