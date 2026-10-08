<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('teacher')->after('password');
            $table->string('title', 120)->nullable()->after('role');
            $table->string('institution', 180)->nullable()->after('title');
            $table->boolean('is_active')->default(true)->after('institution');
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'title', 'institution', 'is_active', 'last_login_at']);
        });
    }
};
