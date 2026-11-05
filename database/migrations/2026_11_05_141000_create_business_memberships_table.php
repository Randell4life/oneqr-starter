<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('business_branches')->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 24)->default('staff');
            $table->string('status', 24)->default('invited');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'user_id'], 'biz_member_unique');
            $table->index(['business_id', 'role', 'status'], 'biz_member_role_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_memberships');
    }
};
