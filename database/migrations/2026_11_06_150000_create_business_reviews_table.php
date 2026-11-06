<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name', 120);
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->index(['business_id', 'status', 'created_at'], 'business_reviews_business_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_reviews');
    }
};
