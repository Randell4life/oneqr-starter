<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table): void {
            $table->string('placement_label', 100)->nullable()->after('category');
            $table->string('campaign_name', 120)->nullable()->after('placement_label');
            $table->index(['business_id', 'campaign_name'], 'qr_business_campaign_idx');
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table): void {
            $table->dropIndex('qr_business_campaign_idx');
            $table->dropColumn(['placement_label', 'campaign_name']);
        });
    }
};
