<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('product');
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->char('currency', 3)->default('GEL');
            $table->string('image_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'slug'], 'biz_catalog_slug_unique');
            $table->index(['business_id', 'is_available', 'sort_order'], 'biz_catalog_listing_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_catalog_items');
    }
};
