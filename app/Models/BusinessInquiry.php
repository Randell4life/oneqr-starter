<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessInquiry extends Model
{
    protected $fillable = [
        'customer_name', 'customer_phone', 'customer_email', 'inquiry_type',
        'preferred_date', 'message', 'status', 'follow_up_at', 'internal_notes',
    ];

    protected function casts(): array
    {
        return ['preferred_date' => 'date', 'follow_up_at' => 'datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
