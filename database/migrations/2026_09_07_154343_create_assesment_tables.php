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
        Schema::create('assesments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('judul');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assesment_aspek', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nama')->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assesment_pertanyaan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('assesment_id');
            $table->unsignedBigInteger('aspek_id');
            $table->text('pertanyaan');
            $table->text('informasi')->nullable();
            $table->text('metode_verifikasi')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('assesment_id')->references('id')->on('assesments')->onDelete('cascade');
            $table->foreign('aspek_id')->references('id')->on('assesment_aspek')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('assesment_id');
            $table->timestamps();
        });

        Schema::create('assesment_pertanyaan_komoditas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pertanyaan_id');
            $table->unsignedBigInteger('komoditas_id');
            $table->decimal('bobot', 5, 2);
            $table->foreign('pertanyaan_id')->references('id')->on('assesment_pertanyaan')->onDelete('cascade');
            $table->foreign('komoditas_id')->references('id')->on('komoditas')->onDelete('cascade');
            $table->unique(['pertanyaan_id', 'komoditas_id']);
            $table->timestamps();
        });

        Schema::create('assesment_jawaban', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pertanyaan_id');
            $table->string('teks_jawaban');
            $table->unsignedInteger('urutan')->default(0);
            $table->foreign('pertanyaan_id')->references('id')->on('assesment_pertanyaan')->onDelete('cascade');
            $table->index('pertanyaan_id');
            $table->timestamps();
        });

        Schema::create('assesment_jawaban_komoditas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('jawaban_id');
            $table->unsignedBigInteger('komoditas_id');
            $table->decimal('skor', 8, 2);
            $table->foreign('jawaban_id')->references('id')->on('assesment_jawaban')->onDelete('cascade');
            $table->foreign('komoditas_id')->references('id')->on('komoditas')->onDelete('cascade');
            $table->unique(['jawaban_id', 'komoditas_id']);
            $table->timestamps();
        });

        Schema::create('assesment_bukti_dukung', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pertanyaan_id');
            $table->string('label');
            $table->unsignedInteger('urutan')->default(0);
            $table->foreign('pertanyaan_id')->references('id')->on('assesment_pertanyaan')->onDelete('cascade');
            $table->index('pertanyaan_id');
            $table->timestamps();
        });

        Schema::create('assesment_isi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('assesment_id');
            $table->unsignedBigInteger('entitas_id');
            $table->unsignedBigInteger('komoditas_id');
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->decimal('skor_total', 8, 2)->nullable();
            $table->string('sertifikat_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('assesment_id')->references('id')->on('assesments')->onDelete('cascade');
            $table->foreign('entitas_id')->references('id')->on('entities')->onDelete('cascade');
            $table->foreign('komoditas_id')->references('id')->on('komoditas')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('entitas_id');
            $table->index('status');
            $table->timestamps();
        });

        Schema::create('assesment_isi_jawaban', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('isi_id');
            $table->unsignedBigInteger('pertanyaan_id');
            $table->unsignedBigInteger('jawaban_entitas_id')->nullable();
            $table->unsignedBigInteger('jawaban_verifikator_id')->nullable();
            $table->foreign('isi_id')->references('id')->on('assesment_isi')->onDelete('cascade');
            $table->foreign('pertanyaan_id')->references('id')->on('assesment_pertanyaan')->onDelete('cascade');
            $table->foreign('jawaban_entitas_id')->references('id')->on('assesment_jawaban')->nullOnDelete();
            $table->foreign('jawaban_verifikator_id')->references('id')->on('assesment_jawaban')->nullOnDelete();
            $table->unique(['isi_id', 'pertanyaan_id']);
            $table->timestamps();
        });

        Schema::create('assesment_isi_bukti', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('isi_id');
            $table->unsignedBigInteger('bukti_dukung_id');
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->foreign('isi_id')->references('id')->on('assesment_isi')->onDelete('cascade');
            $table->foreign('bukti_dukung_id')->references('id')->on('assesment_bukti_dukung')->onDelete('cascade');
            $table->unique(['isi_id', 'bukti_dukung_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assesment_isi_bukti');
        Schema::dropIfExists('assesment_isi_jawaban');
        Schema::dropIfExists('assesment_isi');
        Schema::dropIfExists('assesment_bukti_dukung');
        Schema::dropIfExists('assesment_jawaban_komoditas');
        Schema::dropIfExists('assesment_jawaban');
        Schema::dropIfExists('assesment_pertanyaan_komoditas');
        Schema::dropIfExists('assesment_pertanyaan');
        Schema::dropIfExists('assesment_aspek');
        Schema::dropIfExists('assesments');
    }
};
