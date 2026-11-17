<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembagian Kuota Ekspor - lihat docs/PRD-pembagian-kuota-tangkap.md §7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuota_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('bobot_ekspor', 5, 4)->default(0.3);
            $table->decimal('bobot_evaluasi', 5, 4)->default(0.7);
            $table->decimal('cadangan', 5, 4)->default(0.1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('kuota_settings')->insert([
            'bobot_ekspor' => 0.3,
            'bobot_evaluasi' => 0.7,
            'cadangan' => 0.1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('kuota_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('provinsi_id');
            $table->unsignedBigInteger('komoditas_id');
            $table->unsignedBigInteger('species_id');
            $table->unsignedBigInteger('trade_code_id');
            $table->smallInteger('year');
            $table->decimal('kuota_tangkap', 15, 4);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('latest_snapshot_id')->nullable();
            $table->text('catatan')->nullable();
            $table->text('catatan_pengembalian')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provinsi_id')->references('id')->on('provinsi')->restrictOnDelete();
            $table->foreign('komoditas_id')->references('id')->on('komoditas')->restrictOnDelete();
            $table->foreign('species_id')->references('id')->on('species')->restrictOnDelete();
            $table->foreign('trade_code_id')->references('id')->on('trade_codes')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            $table->index('provinsi_id');
            $table->index('year');
            $table->index('status');
        });

        Schema::create('kuota_calculation_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kuota_period_id');
            $table->unsignedInteger('version');
            $table->decimal('kuota_tangkap', 15, 4);
            $table->decimal('cadangan', 5, 4);
            $table->decimal('bobot_ekspor', 5, 4);
            $table->decimal('bobot_evaluasi', 5, 4);
            $table->integer('kuota_dibagi');
            $table->decimal('total_realisasi_ekspor', 15, 4);
            $table->decimal('total_nilai_evaluasi', 10, 2);
            $table->smallInteger('tahun_realisasi');
            $table->jsonb('rows');
            $table->jsonb('warnings');
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('kuota_period_id')->references('id')->on('kuota_periods')->cascadeOnDelete();
            $table->foreign('calculated_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['kuota_period_id', 'version']);
        });

        Schema::table('kuota_periods', function (Blueprint $table) {
            $table->foreign('latest_snapshot_id')->references('id')->on('kuota_calculation_snapshots')->nullOnDelete();
        });

        // Partial unique: periode yang di-soft-delete tidak menghalangi pembuatan ulang. Dibuat
        // SETELAH alter di atas karena SQLite membangun ulang tabel saat menambah FK dan
        // kehilangan klausa WHERE pada index.
        DB::statement('CREATE UNIQUE INDEX kuota_periods_unique_active ON kuota_periods (provinsi_id, species_id, trade_code_id, year) WHERE deleted_at IS NULL');

        Schema::create('kuota_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kuota_period_id');
            $table->unsignedBigInteger('entity_id');
            $table->decimal('permohonan', 15, 4)->nullable();
            $table->decimal('realisasi_ekspor', 15, 4)->nullable();
            $table->decimal('nilai_evaluasi', 8, 2)->nullable();
            $table->unsignedBigInteger('assesment_isi_id')->nullable();
            $table->decimal('komponen_ekspor', 15, 4)->nullable();
            $table->decimal('komponen_evaluasi', 15, 4)->nullable();
            $table->decimal('rekomendasi', 15, 4)->nullable();
            $table->integer('rekomendasi_bulat')->nullable();
            $table->decimal('ditetapkan', 15, 4)->nullable();
            $table->text('alasan_penyesuaian')->nullable();
            $table->decimal('realisasi_tangkap', 15, 4)->nullable();
            $table->string('sk_tangkap_nomor', 100)->nullable();
            $table->date('sk_tangkap_tanggal')->nullable();
            $table->string('sk_tangkap_path')->nullable();
            $table->timestamps();

            $table->foreign('kuota_period_id')->references('id')->on('kuota_periods')->cascadeOnDelete();
            $table->foreign('entity_id')->references('id')->on('entities')->restrictOnDelete();
            $table->foreign('assesment_isi_id')->references('id')->on('assesment_isi')->nullOnDelete();
            $table->unique(['kuota_period_id', 'entity_id']);
            $table->index('entity_id');
        });

        Schema::create('kuota_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuota_period_id')->nullable()->constrained('kuota_periods')->cascadeOnDelete();
            $table->foreignId('kuota_allocation_id')->nullable()->constrained('kuota_allocations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['kuota_period_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuota_activities');
        Schema::dropIfExists('kuota_allocations');
        Schema::table('kuota_periods', function (Blueprint $table) {
            $table->dropIndex('kuota_periods_unique_active');
            $table->dropForeign(['latest_snapshot_id']);
        });
        Schema::dropIfExists('kuota_calculation_snapshots');
        Schema::dropIfExists('kuota_periods');
        Schema::dropIfExists('kuota_settings');
    }
};
