<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_chat_id')->constrained()->cascadeOnDelete();
            $table->string('body');
            $table->dateTime('remind_at');
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['delivered_at', 'remind_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
