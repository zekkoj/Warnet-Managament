<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueLog extends Model
{
    protected $table = 'revenue_logs';
    
    protected $fillable = [
        'type',
        'amount',
        'date',
        'hour',
        'category',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];
}
