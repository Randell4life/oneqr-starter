<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_orders', function (Blueprint $table) {
            $table->string('table_label', 80)->nullable()->after('business_id');
            $table->index(['business_id', 'table_label', 'created_at'], 'biz_orders_table_idx');
        });
    }

    public function down(): void
    {
        Schema::table('business_orders', function (Blueprint $table) {
            $table->dropIndex('biz_orders_table_idx');
            $table->dropColumn('table_label');
        });
    }
};
