<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartPageSchedule extends Model
{
    protected $fillable = ['business_id', 'smart_page_id', 'name', 'weekdays', 'starts_at', 'ends_at', 'starts_on', 'ends_on', 'priority', 'is_override', 'is_active'];

    protected function casts(): array
    {
        return ['weekdays' => 'array', 'starts_on' => 'date', 'ends_on' => 'date', 'is_override' => 'boolean', 'is_active' => 'boolean', 'priority' => 'integer'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SmartPage::class, 'smart_page_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
