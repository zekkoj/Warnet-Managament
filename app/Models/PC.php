<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PC extends Model
{
    protected $table = 'pcs';
    
    protected $fillable = [
        'pc_code',
        'location',
        'position',
        'type',
        'status',
        'specifications',
        'current_session_id',
        'cpu_usage',
        'ram_usage',
        'disk_usage',
        'current_process',
        'last_heartbeat',
    ];

    protected $casts = [
        'current_process' => 'array',
        'last_heartbeat' => 'datetime',
    ];

    protected $appends = ['tier', 'active_session', 'remaining_time', 'elapsed_time'];

    // Accessor for tier (alias for type)
    public function getTierAttribute(): string
    {
        return $this->type;
    }

    // Accessor for active_session (alias for currentSession relationship data)
    public function getActiveSessionAttribute()
    {
        return $this->currentSession;
    }

    // Accessor for remaining time in seconds
    public function getRemainingTimeAttribute(): int
    {
        if (!$this->currentSession || !$this->currentSession->start_time) {
            return 0;
        }

        $elapsedSeconds = now()->diffInSeconds($this->currentSession->start_time);
        $totalSeconds = $this->currentSession->duration * 60; // duration is in minutes
        $remaining = max(0, $totalSeconds - $elapsedSeconds);

        return $remaining;
    }

    // Accessor for elapsed time in seconds
    public function getElapsedTimeAttribute(): int
    {
        if (!$this->currentSession || !$this->currentSession->start_time) {
            return 0;
        }

        return now()->diffInSeconds($this->currentSession->start_time);
    }

    // Relationships
    public function currentSession(): BelongsTo
    {
        return $this->belongsTo(RentalSession::class, 'current_session_id');
    }

    public function rentalSessions(): HasMany
    {
        return $this->hasMany(RentalSession::class, 'pc_id');
    }
}
