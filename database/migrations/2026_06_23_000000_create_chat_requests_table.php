<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['recipient_id', 'status']);
            $table->index(['requester_id', 'status']);
            // One row per directed pair. Re-requests update the existing row's
            // status (history is preserved in audit_logs, not here).
            $table->unique(['requester_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_requests');
    }
};
