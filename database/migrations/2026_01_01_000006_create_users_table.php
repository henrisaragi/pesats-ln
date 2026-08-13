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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('entity_id')->nullable();   // null utk petugas internal/UPT
            $table->string('nip')->nullable();                     // NIP pegawai (bila internal)
            $table->string('jabatan')->nullable();                 // untuk dicetak pada dokumen izin
            $table->string('signature_path')->nullable();          // TTD elektronik penandatangan
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('entity_id')->references('id')->on('entities')->nullOnDelete();
            $table->index('entity_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
