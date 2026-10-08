<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('type', 30)->default('quiz');
            $table->text('instructions')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('payload')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();

            $table->unique(['course_id', 'slug']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->string('type', 30)->default('multiple');
            $table->text('prompt');
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->text('explanation')->nullable();
            $table->integer('points')->default(1);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'position']);
        });

        Schema::create('bibliography', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30)->default('principal');
            $table->string('authors')->nullable();
            $table->string('title');
            $table->string('edition', 120)->nullable();
            $table->string('publisher', 180)->nullable();
            $table->string('year', 20)->nullable();
            $table->string('url')->nullable();
            $table->text('note')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bibliography');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('activities');
    }
};
