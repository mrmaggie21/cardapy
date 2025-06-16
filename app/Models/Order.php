<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY = 'ready';
    const STATUS_DELIVERING = 'delivering';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_APPROVED = 'approved';
    const PAYMENT_STATUS_REJECTED = 'rejected';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    const DELIVERY_PICKUP = 'pickup';
    const DELIVERY_DELIVERY = 'delivery';

    protected $fillable = [
        'restaurant_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_type',
        'delivery_address',
        'delivery_complement',
        'delivery_neighborhood',
        'delivery_city',
        'delivery_zip_code',
        'delivery_latitude',
        'delivery_longitude',
        'subtotal',
        'delivery_fee',
        'discount',
        'total',
        'status',
        'payment_status',
        'payment_method',
        'payment_id',
        'mercadopago_payment_id',
        'notes',
        'estimated_delivery_time',
        'confirmed_at',
        'prepared_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'confirmed_at' => 'datetime',
        'prepared_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'estimated_delivery_time' => 'datetime'
    ];

    /**
     * Relacionamento com restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relacionamento com itens do pedido
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relacionamento com pagamento
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Relacionamento com avaliação
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Scopes para diferentes status
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePreparing($query)
    {
        return $query->where('status', self::STATUS_PREPARING);
    }

    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }

    public function scopeDelivering($query)
    {
        return $query->where('status', self::STATUS_DELIVERING);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope para pedidos de hoje
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope para pedidos por período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Número do pedido formatado
     */
    public function getOrderNumberAttribute(): string
    {
        return 'PED-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Status formatado para exibição
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_CONFIRMED => 'Confirmado',
            self::STATUS_PREPARING => 'Preparando',
            self::STATUS_READY => 'Pronto',
            self::STATUS_DELIVERING => 'Saiu para entrega',
            self::STATUS_DELIVERED => 'Entregue',
            self::STATUS_CANCELLED => 'Cancelado',
            default => 'Desconhecido'
        };
    }

    /**
     * Cor do status para exibição
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_PREPARING => 'orange',
            self::STATUS_READY => 'purple',
            self::STATUS_DELIVERING => 'indigo',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_CANCELLED => 'red',
            default => 'gray'
        };
    }

    /**
     * Verifica se o pedido pode ser cancelado
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING
        ]);
    }

    /**
     * Verifica se o pedido está finalizado
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED
        ]);
    }

    /**
     * Calcula o tempo de entrega estimado
     */
    public function calculateEstimatedDeliveryTime(): \Carbon\Carbon
    {
        $baseTime = $this->delivery_type === self::DELIVERY_PICKUP ? 20 : 45;
        $preparationTime = $this->items->sum(fn($item) => $item->menuItem->preparation_time ?? 10);
        
        return $this->created_at->addMinutes($baseTime + $preparationTime);
    }

    /**
     * Endereço completo de entrega
     */
    public function getFullDeliveryAddressAttribute(): string
    {
        if ($this->delivery_type === self::DELIVERY_PICKUP) {
            return 'Retirada no local';
        }

        $address = $this->delivery_address;
        if ($this->delivery_complement) {
            $address .= ', ' . $this->delivery_complement;
        }
        $address .= ' - ' . $this->delivery_neighborhood;
        $address .= ', ' . $this->delivery_city;
        
        return $address;
    }

    /**
     * Quantidade total de itens
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }
} 