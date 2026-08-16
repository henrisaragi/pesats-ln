<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sats_ln', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('sats_ln_id');
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('sats_ln_id')->references('id')->on('sats_ln')->cascadeOnDelete();
            $table->unique(['document_id', 'sats_ln_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sats_ln');
    }
};
