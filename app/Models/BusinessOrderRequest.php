<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessOrderRequest extends Model
{
    protected $fillable = [
        'business_id', 'catalog_item_id', 'item_name', 'unit_price', 'currency',
        'quantity', 'customer_name', 'customer_phone', 'customer_email',
        'message', 'status', 'follow_up_at', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'follow_up_at' => 'datetime',
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
}
