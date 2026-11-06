<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSubscriptionAudit extends Model
{
    protected $fillable = [
        'business_id', 'actor_user_id', 'previous_subscription_id',
        'new_subscription_id', 'previous_plan_id', 'new_plan_id',
        'duration_months', 'reason',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function previousPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'previous_plan_id');
    }

    public function newPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'new_plan_id');
    }
}
