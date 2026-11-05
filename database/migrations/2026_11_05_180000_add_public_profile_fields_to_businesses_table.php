<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('tagline', 180)->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('accent_color', 7)->default('#8B5CF6');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'description', 'cover_path', 'address', 'city', 'accent_color', 'is_published', 'published_at']);
        });
    }
};
