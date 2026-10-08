<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('type', 30)->default('unit');
            $table->integer('position')->default(0);
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();

            $table->unique(['course_id', 'type', 'slug']);
            $table->index(['course_id', 'position']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->integer('position')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->longText('content')->nullable();
            $table->text('summary')->nullable();
            $table->text('objectives')->nullable();
            $table->json('sources')->nullable();
            $table->json('related_document_ids')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->integer('version')->default(1);
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'slug']);
            $table->index(['course_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('modules');
    }
};
