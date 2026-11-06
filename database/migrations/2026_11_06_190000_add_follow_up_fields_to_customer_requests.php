<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_inquiries', function (Blueprint $table): void {
            $table->dateTime('follow_up_at')->nullable();
            $table->text('internal_notes')->nullable();
        });

        Schema::table('business_order_requests', function (Blueprint $table): void {
            $table->dateTime('follow_up_at')->nullable();
            $table->text('internal_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('business_order_requests', function (Blueprint $table): void {
            $table->dropColumn(['follow_up_at', 'internal_notes']);
        });

        Schema::table('business_inquiries', function (Blueprint $table): void {
            $table->dropColumn(['follow_up_at', 'internal_notes']);
        });
    }
};
