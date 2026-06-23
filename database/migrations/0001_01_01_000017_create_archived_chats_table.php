<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->timestamp('archived_at')->useCurrent();

            $table->unique(['user_id', 'conversation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_chats');
    }
};
