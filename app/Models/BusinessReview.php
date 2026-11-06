<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessReview extends Model
{
    protected $fillable = ['customer_name', 'rating', 'comment', 'status'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
