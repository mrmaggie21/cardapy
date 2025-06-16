<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'item_name',
        'item_description',
        'price',
        'quantity',
        'customizations',
        'notes'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'customizations' => 'array'
    ];

    /**
     * Relacionamento com pedido
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relacionamento com item do cardápio
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Subtotal do item
     */
    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }
} 