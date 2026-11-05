<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'tagline',
        'description',
        'slug',
        'business_type',
        'email',
        'phone',
        'website',
        'logo_path',
        'cover_path',
        'address',
        'city',
        'accent_color',
        'is_published',
        'published_at',
        'timezone',
        'default_locale',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(BusinessBranch::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(BusinessMembership::class);
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(BusinessCatalogItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(BusinessOrder::class);
    }

    public function orderRequests(): HasMany
    {
        return $this->hasMany(BusinessOrderRequest::class);
    }

    public function smartPages(): HasMany
    {
        return $this->hasMany(SmartPage::class);
    }

    public function smartPageSchedules(): HasMany
    {
        return $this->hasMany(SmartPageSchedule::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(BusinessInventoryMovement::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(BusinessSubscription::class);
    }
}
