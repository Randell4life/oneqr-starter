<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 160);
            $table->string('category', 32)->default('general');
            $table->string('priority', 16)->default('normal');
            $table->string('status', 24)->default('open');
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority', 'last_reply_at'], 'support_ticket_queue_idx');
            $table->index(['created_by_user_id', 'created_at'], 'support_ticket_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
