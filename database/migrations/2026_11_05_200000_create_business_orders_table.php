<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 32)->unique();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 40);
            $table->string('customer_email', 190)->nullable();
            $table->text('customer_address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->char('currency', 3)->default('GEL');
            $table->string('status', 24)->default('new');
            $table->string('payment_method', 24)->default('cash_on_delivery');
            $table->string('payment_status', 24)->default('unpaid');
            $table->timestamps();

            $table->index(['business_id', 'status', 'created_at'], 'biz_orders_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_orders');
    }
};
