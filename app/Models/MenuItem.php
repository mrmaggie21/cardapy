<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'promotional_price',
        'image',
        'ingredients',
        'allergens',
        'nutritional_info',
        'preparation_time',
        'is_available',
        'is_featured',
        'sort_order',
        'calories',
        'serves',
        'tags'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'promotional_price' => 'decimal:2',
        'ingredients' => 'array',
        'allergens' => 'array',
        'nutritional_info' => 'array',
        'tags' => 'array',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'calories' => 'integer',
        'serves' => 'integer',
        'preparation_time' => 'integer'
    ];

    /**
     * Relacionamento com restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relacionamento com categoria
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relacionamento com itens do pedido
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relacionamento com avaliações
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope para itens disponíveis
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope para itens em destaque
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope por categoria
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope ordenado
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * URL da imagem
     */
    public function getImageUrlAttribute(): string
    {
        return $this->image ? asset('storage/' . $this->image) : asset('images/default-item.jpg');
    }

    /**
     * Preço efetivo (promocional ou normal)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->promotional_price ?? $this->price;
    }

    /**
     * Verifica se está em promoção
     */
    public function getIsOnSaleAttribute(): bool
    {
        return !is_null($this->promotional_price) && $this->promotional_price < $this->price;
    }

    /**
     * Desconto percentual
     */
    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->is_on_sale) {
            return 0;
        }

        return round((($this->price - $this->promotional_price) / $this->price) * 100);
    }

    /**
     * Avaliação média do item
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Total de avaliações do item
     */
    public function getTotalReviewsAttribute(): int
    {
        return $this->reviews()->count();
    }

    /**
     * Configuração do Scout (Elasticsearch)
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
            'tags' => $this->tags,
            'category' => $this->category->name ?? '',
            'price' => $this->effective_price,
            'is_available' => $this->is_available,
            'restaurant_id' => $this->restaurant_id
        ];
    }

    /**
     * Nome do índice do Scout
     */
    public function searchableAs(): string
    {
        return 'menu_items_index';
    }

    /**
     * Condições para indexação
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_available;
    }
} 