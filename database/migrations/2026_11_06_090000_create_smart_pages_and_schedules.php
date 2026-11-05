<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->string('kind', 20)->default('variant');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_published')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'is_default', 'is_published']);
        });

        Schema::create('smart_page_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('smart_page_id')->constrained('smart_pages')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title', 180)->nullable();
            $table->json('content')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['smart_page_id', 'is_visible', 'sort_order']);
        });

        Schema::create('smart_page_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('smart_page_id')->constrained('smart_pages')->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('weekdays')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedSmallInteger('priority')->default(10);
            $table->boolean('is_override')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['business_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_page_schedules');
        Schema::dropIfExists('smart_page_blocks');
        Schema::dropIfExists('smart_pages');
    }
};
