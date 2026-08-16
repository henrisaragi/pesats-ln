<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('permohonan_id')->nullable();
            $table->string('nomor_document');
            $table->string('type_document');
            $table->date('issued_date');
            $table->unsignedBigInteger('issued_country_id');
            $table->string('path');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('entity_id')->references('id')->on('entities')->restrictOnDelete();
            $table->foreign('created_by_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('permohonan_id')->references('id')->on('permohonan')->nullOnDelete();
            $table->foreign('issued_country_id')->references('id')->on('countries')->restrictOnDelete();
            $table->index('entity_id');
            $table->index('permohonan_id');
            $table->index('type_document');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
