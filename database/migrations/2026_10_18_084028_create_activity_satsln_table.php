<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Milestone aktivitas Permohonan & SATS-LN (perubahan status, verifikasi, pesan verifikator) -
     * sats_ln_id null berarti aktivitas tingkat Permohonan. Lihat claude/verifikasi-satsln.md.
     */
    public function up(): void
    {
        Schema::create('activity_satsln', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan')->cascadeOnDelete();
            $table->foreignId('sats_ln_id')->nullable()->constrained('sats_ln')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('messages');
            $table->timestamps();

            $table->index(['permohonan_id', 'created_at']);
            $table->index('sats_ln_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_satsln');
    }
};
