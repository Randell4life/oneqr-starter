<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_customer_interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('contactable_type');
            $table->unsignedBigInteger('contactable_id');
            $table->index(['contactable_type', 'contactable_id'], 'bc_interactions_contactable_idx');
            $table->string('channel', 24);
            $table->text('summary');
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['business_id', 'occurred_at'], 'business_customer_interactions_business_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_customer_interactions');
    }
};
