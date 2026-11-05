<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('business_id')
                ->constrained('business_branches')
                ->nullOnDelete();

            $table->index(['business_id', 'branch_id'], 'qr_business_branch_idx');
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropIndex('qr_business_branch_idx');
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
