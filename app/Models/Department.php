<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'leave_workflow_id',
        'max_concurrent_leave',
        'leave_policy_notes'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
