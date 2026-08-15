<?php

declare(strict_types=1);

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
        Schema::create('conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('initiator_id');
            $table->unsignedBigInteger('claimed_by')->nullable();
            $table->string('status')->default('waiting');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('ended_by')->nullable();
            $table->foreign('initiator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('claimed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('ended_by')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('conversation_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('conversations');
    }
};
