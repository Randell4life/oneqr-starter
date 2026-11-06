<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_page_interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('interaction_type', 32);
            $table->timestamp('occurred_at')->index();
            $table->index(['business_id', 'occurred_at']);
            $table->index(['business_id', 'interaction_type', 'occurred_at'], 'spi_business_type_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_page_interactions');
    }
};
