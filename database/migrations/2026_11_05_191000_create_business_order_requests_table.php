<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_order_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained('business_catalog_items')->nullOnDelete();
            $table->string('item_name', 160);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->char('currency', 3)->default('GEL');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('customer_name', 120);
            $table->string('customer_phone', 40);
            $table->string('customer_email', 190)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new');
            $table->timestamps();

            $table->index(['business_id', 'status', 'created_at'], 'biz_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_order_requests');
    }
};
