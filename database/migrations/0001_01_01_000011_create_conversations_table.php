<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private'])->default('private');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->index(['type', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
