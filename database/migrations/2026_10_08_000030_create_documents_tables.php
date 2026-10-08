<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('type', 20)->default('txt');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('source_url')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'status']);
        });

        Schema::create('content_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->string('source_type', 20)->default('document');
            $table->string('source_label');
            $table->string('section', 255)->nullable();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->unsignedInteger('page_from')->nullable();
            $table->unsignedInteger('page_to')->nullable();
            $table->text('content');
            $table->timestamps();

            $table->index(['course_id', 'source_type']);
            $table->index(['document_id', 'chunk_index']);
        });

        $dim = (int) config('ai.embedding_dim', 768);

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::getConnection()->statement('CREATE EXTENSION IF NOT EXISTS vector');
            Schema::getConnection()->statement(
                "ALTER TABLE content_chunks ADD COLUMN IF NOT EXISTS embedding vector({$dim})"
            );
        } else {
            Schema::table('content_chunks', function (Blueprint $table) {
                $table->text('embedding')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_chunks');
        Schema::dropIfExists('documents');
    }
};
