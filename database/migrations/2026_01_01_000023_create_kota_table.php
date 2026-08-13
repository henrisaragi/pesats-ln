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
        Schema::create('kota', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('provinsi_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();

            $table->foreign('provinsi_id')->references('id')->on('provinsi')->restrictOnDelete();
            $table->index('provinsi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kota');
    }
};
