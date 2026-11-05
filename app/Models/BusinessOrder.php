<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessOrder extends Model
{
    protected $fillable = [
        'business_id', 'order_number', 'customer_name', 'customer_phone',
        'customer_email', 'customer_address', 'notes', 'subtotal', 'total',
        'currency', 'status', 'payment_method', 'payment_status', 'stock_deducted_at', 'stock_restored_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'stock_deducted_at' => 'datetime',
            'stock_restored_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BusinessOrderItem::class);
    }
}
