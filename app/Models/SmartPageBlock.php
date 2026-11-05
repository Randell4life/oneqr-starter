<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartPageBlock extends Model
{
    protected $fillable = ['smart_page_id', 'type', 'title', 'content', 'is_visible', 'sort_order'];

    protected function casts(): array
    {
        return ['content' => 'array', 'is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SmartPage::class, 'smart_page_id');
    }
}
