<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Multitenancy\Models\Tenant;

class Restaurant extends Tenant
{
    use HasFactory;
    use UsesTenantConnection;

    protected $fillable = [
        'name',
        'subdomain',
        'domain',
        'slug',
        'description',
        'logo',
        'banner',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'opening_hours',
        'delivery_fee',
        'minimum_order',
        'delivery_time',
        'payment_methods',
        'is_active',
        'theme_color',
        'theme_config',
        'mercadopago_public_key',
        'mercadopago_access_token',
        'shard_id'
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'payment_methods' => 'array',
        'theme_config' => 'array',
        'is_active' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'minimum_order' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    /**
     * Configuração de conexão dinâmica baseada no shard
     */
    public function getDatabaseName(): string
    {
        return config('database.tenant_prefix') . $this->shard_id;
    }

    /**
     * Relacionamento com categorias do cardápio
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Relacionamento com itens do cardápio
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Relacionamento com pedidos
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relacionamento com usuários do restaurante
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'permissions');
    }

    /**
     * Relacionamento com avaliações
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope para restaurantes ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * URL completa do restaurante
     */
    public function getUrlAttribute(): string
    {
        return "https://{$this->subdomain}." . config('app.cardapy_domain');
    }

    /**
     * URL da logo
     */
    public function getLogoUrlAttribute(): string
    {
        return $this->logo ? asset('storage/' . $this->logo) : asset('images/default-logo.png');
    }

    /**
     * URL do banner
     */
    public function getBannerUrlAttribute(): string
    {
        return $this->banner ? asset('storage/' . $this->banner) : asset('images/default-banner.jpg');
    }

    /**
     * Verifica se o restaurante está aberto
     */
    public function isOpen(): bool
    {
        $now = now();
        $currentDay = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        if (!isset($this->opening_hours[$currentDay])) {
            return false;
        }

        $hours = $this->opening_hours[$currentDay];
        
        if ($hours['closed']) {
            return false;
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    /**
     * Calcula avaliação média
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Total de avaliações
     */
    public function getTotalReviewsAttribute(): int
    {
        return $this->reviews()->count();
    }
} 