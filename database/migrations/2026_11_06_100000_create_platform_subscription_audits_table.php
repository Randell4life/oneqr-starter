<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_subscription_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_subscription_id')->nullable()->constrained('business_subscriptions')->nullOnDelete();
            $table->foreignId('new_subscription_id')->nullable()->constrained('business_subscriptions')->nullOnDelete();
            $table->foreignId('previous_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->foreignId('new_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->unsignedTinyInteger('duration_months')->nullable();
            $table->string('reason', 500);
            $table->timestamps();
            $table->index(['business_id', 'created_at'], 'platform_sub_audit_biz_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_subscription_audits');
    }
};
