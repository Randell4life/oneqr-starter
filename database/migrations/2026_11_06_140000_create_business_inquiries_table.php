<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 40);
            $table->string('customer_email', 190)->nullable();
            $table->string('inquiry_type', 32)->default('general');
            $table->date('preferred_date')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new');
            $table->timestamps();
            $table->index(['business_id', 'status', 'created_at'], 'business_inquiries_business_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_inquiries');
    }
};
