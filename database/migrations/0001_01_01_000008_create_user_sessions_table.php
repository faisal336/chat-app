<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('device_name')->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('signed_out_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'signed_out_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
