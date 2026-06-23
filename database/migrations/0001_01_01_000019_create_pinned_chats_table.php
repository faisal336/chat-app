<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinned_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('pinned_at')->useCurrent();

            $table->unique(['user_id', 'conversation_id']);
            $table->index(['user_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinned_chats');
    }
};
