<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assesment_isi_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('isi_id');
            $table->unsignedBigInteger('pertanyaan_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreign('isi_id')->references('id')->on('assesment_isi')->onDelete('cascade');
            $table->foreign('pertanyaan_id')->references('id')->on('assesment_pertanyaan')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['isi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assesment_isi_activities');
    }
};
