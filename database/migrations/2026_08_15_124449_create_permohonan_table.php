<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('entity_id');
            $table->string('nomor_permohonan')->unique();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('entity_id')->references('id')->on('entities')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index('entity_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan');
    }
};
