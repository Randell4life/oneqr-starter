<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude'], 'businesses_geo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropIndex('businesses_geo_idx');
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
