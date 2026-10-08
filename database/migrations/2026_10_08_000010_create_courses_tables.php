<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('institution', 180)->nullable();
            $table->string('career', 180)->nullable();
            $table->string('course_year', 80)->nullable();
            $table->string('duration', 120)->nullable();
            $table->string('modality', 120)->nullable();
            $table->text('objectives')->nullable();
            $table->text('program')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('primary_color', 20)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('structure_generated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('course_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('collaborator');
            $table->timestamps();
            $table->unique(['course_id', 'user_id']);
        });

        Schema::create('course_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->boolean('ai_assistant_enabled')->default(true);
            $table->boolean('allow_external_knowledge')->default(false);
            $table->boolean('show_sources')->default(true);
            $table->boolean('show_progress')->default(true);
            $table->boolean('allow_downloads')->default(true);
            $table->boolean('enable_search')->default(true);
            $table->boolean('auto_generate_resources')->default(false);
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_settings');
        Schema::dropIfExists('course_teachers');
        Schema::dropIfExists('courses');
    }
};
