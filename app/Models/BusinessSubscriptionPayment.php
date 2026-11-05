<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSubscriptionPayment extends Model
{
    protected $fillable = [
        'uuid', 'business_id', 'subscription_plan_id', 'provider', 'status',
        'amount', 'currency', 'merchant_payment_id', 'provider_payment_id',
        'checkout_url', 'expires_at', 'verified_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}
