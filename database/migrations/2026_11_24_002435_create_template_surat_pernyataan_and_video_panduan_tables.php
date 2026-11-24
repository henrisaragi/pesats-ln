<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_surat_pernyataan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('judul');
            $table->text('keterangan')->nullable();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->jsonb('fitur');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('is_active');
            $table->timestamps();
        });

        Schema::create('video_panduan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('judul');
            $table->text('keterangan')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->jsonb('fitur');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('is_active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_panduan');
        Schema::dropIfExists('template_surat_pernyataan');
    }
};
