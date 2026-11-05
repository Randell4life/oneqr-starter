<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_catalog_items', function (Blueprint $table) {
            $table->string('sku', 80)->nullable()->after('slug');
            $table->boolean('track_inventory')->default(false)->after('is_available');
            $table->unsignedInteger('stock_quantity')->nullable()->after('track_inventory');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('stock_quantity');
            $table->unique(['business_id', 'sku'], 'biz_catalog_sku_unique');
            $table->index(['business_id', 'track_inventory', 'stock_quantity'], 'biz_catalog_stock_idx');
        });

        Schema::table('business_orders', function (Blueprint $table) {
            $table->timestamp('stock_deducted_at')->nullable()->after('payment_status');
            $table->timestamp('stock_restored_at')->nullable()->after('stock_deducted_at');
        });

        Schema::create('business_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained('business_catalog_items')->nullOnDelete();
            $table->foreignId('business_order_id')->nullable()->constrained('business_orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24);
            $table->integer('quantity');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'created_at'], 'biz_inventory_history_idx');
            $table->index(['catalog_item_id', 'created_at'], 'biz_inventory_item_history_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_inventory_movements');

        Schema::table('business_orders', function (Blueprint $table) {
            $table->dropColumn(['stock_deducted_at', 'stock_restored_at']);
        });

        Schema::table('business_catalog_items', function (Blueprint $table) {
            $table->dropIndex('biz_catalog_stock_idx');
            $table->dropUnique('biz_catalog_sku_unique');
            $table->dropColumn(['sku', 'track_inventory', 'stock_quantity', 'low_stock_threshold']);
        });
    }
};
