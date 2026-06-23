<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 32)->unique();
            $table->string('name', 80);
            $table->string('email')->nullable()->unique();
            $table->string('pin_hash');
            $table->string('avatar_path')->nullable();
            $table->enum('status', ['active', 'disabled', 'archived'])->default('active');
            $table->enum('theme', ['light', 'dark', 'system'])->default('system');
            $table->boolean('pin_must_change')->default(false);
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_active_at')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
