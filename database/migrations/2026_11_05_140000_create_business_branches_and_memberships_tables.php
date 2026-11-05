<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('timezone', 64)->default('Asia/Tbilisi');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'slug'], 'biz_branch_slug_unique');
            $table->index(['business_id', 'is_active']);
        });

        Schema::create('business_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('business_branches')->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 24)->default('staff');
            $table->string('status', 24)->default('active');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'user_id'], 'biz_member_unique');
            $table->index(['business_id', 'role', 'status'], 'biz_member_role_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_memberships');
        Schema::dropIfExists('business_branches');
    }
};
