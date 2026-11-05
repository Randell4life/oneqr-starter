<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_order_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('tbc');
            $table->string('status', 24)->default('pending');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('GEL');
            $table->string('merchant_payment_id', 52)->unique();
            $table->string('provider_payment_id', 100)->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['business_order_id', 'status'], 'biz_order_payment_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_order_payments');
    }
};
