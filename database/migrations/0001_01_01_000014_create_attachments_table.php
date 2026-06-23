<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploader_id')->constrained('users')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index('message_id');
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
