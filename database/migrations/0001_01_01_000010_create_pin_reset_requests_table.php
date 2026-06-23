<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pin_reset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'consumed'])->default('pending');
            $table->string('requester_ip', 45)->nullable();
            $table->text('reason')->nullable();
            $table->string('temp_pin_hash')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pin_reset_requests');
    }
};
