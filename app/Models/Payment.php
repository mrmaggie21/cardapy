<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'mercadopago_payment_id',
        'payment_method',
        'amount',
        'status',
        'status_detail',
        'external_reference',
        'qr_code',
        'qr_code_base64',
        'ticket_url',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime'
    ];

    /**
     * Relacionamento com pedido
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Verifica se o pagamento foi aprovado
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verifica se o pagamento foi rejeitado
     */
    public function isRejected(): bool
    {
        return in_array($this->status, ['rejected', 'cancelled']);
    }

    /**
     * Verifica se o pagamento está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
} 