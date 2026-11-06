<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'business_id'], 'business_favorites_user_business_unique');
            $table->index(['user_id', 'created_at'], 'business_favorites_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_favorites');
    }
};
