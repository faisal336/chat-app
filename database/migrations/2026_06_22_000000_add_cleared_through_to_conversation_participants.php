<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->unsignedBigInteger('cleared_through_message_id')->nullable()->after('last_read_message_id');
            $table->timestamp('cleared_at')->nullable()->after('cleared_through_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn(['cleared_through_message_id', 'cleared_at']);
        });
    }
};
