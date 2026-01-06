<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'rental_session_id',
        'amount',
        'method',
        'status',
        'qris_code',
        'transaction_ref',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function rentalSession(): BelongsTo
    {
        return $this->belongsTo(RentalSession::class, 'rental_session_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'payment_id');
    }

    public function rentalSessions(): HasMany
    {
        return $this->hasMany(RentalSession::class, 'payment_id');
    }
}
