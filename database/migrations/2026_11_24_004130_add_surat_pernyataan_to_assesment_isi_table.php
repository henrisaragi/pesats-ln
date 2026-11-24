<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assesment_isi', function (Blueprint $table) {
            $table->string('surat_pernyataan_path')->nullable()->after('sertifikat_path');
            $table->string('surat_pernyataan_filename')->nullable()->after('surat_pernyataan_path');
        });
    }

    public function down(): void
    {
        Schema::table('assesment_isi', function (Blueprint $table) {
            $table->dropColumn(['surat_pernyataan_path', 'surat_pernyataan_filename']);
        });
    }
};
