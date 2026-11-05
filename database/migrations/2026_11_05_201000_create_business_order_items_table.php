<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained('business_catalog_items')->nullOnDelete();
            $table->string('item_name', 160);
            $table->decimal('unit_price', 12, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index(['business_order_id', 'catalog_item_id'], 'biz_order_items_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_order_items');
    }
};
