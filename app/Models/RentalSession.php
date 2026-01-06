<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalSession extends Model
{
    protected $table = 'rental_sessions';
    
    protected $fillable = [
        'pc_id',
        'start_time',
        'duration',
        'end_time',
        'user_name',
        'tier',
        'status',
        'total_cost',
        'paid',
        'payment_id',
        'payment_method',
        'paused_at',
        'paused_duration',
        'remaining_seconds', // CRITICAL: Allow mass assignment for pause/resume functionality
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'paused_at' => 'datetime',
        'total_cost' => 'decimal:2',
        'paid' => 'boolean',
    ];

    // Relationships
    public function pc(): BelongsTo
    {
        return $this->belongsTo(PC::class, 'pc_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'rental_session_id');
    }
}
