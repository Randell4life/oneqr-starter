<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmartPage extends Model
{
    protected $fillable = ['business_id', 'name', 'slug', 'kind', 'is_default', 'is_published', 'settings'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_published' => 'boolean', 'settings' => 'array'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(SmartPageBlock::class)->orderBy('sort_order')->orderBy('id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SmartPageSchedule::class);
    }
}
