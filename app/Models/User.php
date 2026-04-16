<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'employee_code',
        'email',
        'password',
        'position', // Kept for legacy compatibility
        'position_id', // New structural position
        'department',
        'phone',
        'address',
        'join_date',
        'role',
        'is_active',
        'shift_id',
        'location_id',
        'supervisor_id',
        'remaining_leave',
        'sick_leave_remaining',
        'leave_reset_year',
        'last_login',
        'profile_photo',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     */
    protected $appends = [
        'current_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'join_date' => 'date',
            'is_active' => 'boolean',
            'last_login' => 'datetime',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn($value) => decryptData($value),
            set: fn($value) => encryptData($value),
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn($value) => decryptData($value),
            set: fn($value) => encryptData($value),
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn($value) => decryptData($value),
            set: fn($value) => encryptData($value),
        );
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn($value) => decryptData($value),
            set: fn($value) => encryptData($value),
        );
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function getReportingManager()
    {
        if ($this->position_id) {
            $currentPosition = $this->jobPosition;
            
            while ($currentPosition && $currentPosition->parent_id) {
                $parentPosition = $currentPosition->parent;
                $approver = $parentPosition->users()
                    ->where('is_active', true)
                    ->where('department', $this->department) // Ensure same department for SPV/Manager level
                    ->first();
                
                // If special case (e.g. Director overseeing all), you might want to relax department check
                // for top-level positions, but let's follow user's strict request first.
                
                if ($approver) {
                    return $approver;
                }
                
                // If vacant or wrong department, climb higher
                $currentPosition = $parentPosition;
            }
        }

        // Fallback to legacy static supervisor if set
        return $this->supervisor;
    }



    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class, 'location_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    /**
     * Get the current role name (for backward compatibility)
     */
    public function getCurrentRoleAttribute(): string
    {
        return $this->roles->first()?->name ?? $this->attributes['role'] ?? 'employee';
    }
}

