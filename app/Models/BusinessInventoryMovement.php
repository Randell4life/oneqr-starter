<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class BusinessInventoryMovement extends Model
{
    protected $fillable = [
        'business_id', 'catalog_item_id', 'business_order_id', 'user_id',
        'type', 'quantity', 'stock_before', 'stock_after', 'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(BusinessCatalogItem::class, 'catalog_item_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(BusinessOrder::class, 'business_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
