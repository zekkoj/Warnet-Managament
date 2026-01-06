<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'category',
        'price',
        'description',
        'available',
        'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'available' => 'boolean',
    ];

    // Relationships
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'menu_id');
    }
}
