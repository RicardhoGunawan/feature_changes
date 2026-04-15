<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = ['name', 'parent_id', 'department', 'level'];

    /**
     * Get the parent position (Superior)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'parent_id');
    }

    /**
     * Get the sub-levels under this position (Subordinates)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Position::class, 'parent_id')->orderBy('level', 'desc');
    }

    /**
     * Get users currently assigned to this position
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'position_id');
    }
}
