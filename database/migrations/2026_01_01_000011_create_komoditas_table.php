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
        Schema::create('komoditas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('komoditas_entitas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('komoditas_id');
            $table->unsignedBigInteger('entitas_id');
            $table->string('file_path');
            $table->date('tanggal_terbit');
            $table->date('tanggal_berakhir');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreign('komoditas_id')->references('id')->on('komoditas')->onDelete('cascade');
            $table->foreign('entitas_id')->references('id')->on('entities')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index('komoditas_id');
            $table->index('entitas_id');
            $table->index('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komoditas_entitas');
        Schema::dropIfExists('komoditas');
    }
};
