<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessCatalogItem extends Model
{
    protected $fillable = [
        'business_id', 'type', 'name', 'slug', 'sku', 'description', 'price',
        'currency', 'image_path', 'is_available', 'track_inventory', 'stock_quantity', 'low_stock_threshold', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(BusinessInventoryMovement::class, 'catalog_item_id');
    }

    public function orderRequests(): HasMany
    {
        return $this->hasMany(BusinessOrderRequest::class, 'catalog_item_id');
    }
}
