<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('notifications_sound')->default(true);
            $table->boolean('show_online_status')->default(true);
            $table->boolean('show_read_receipts')->default(true);
            $table->boolean('enter_to_send')->default(true);
            $table->string('chat_wallpaper')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
