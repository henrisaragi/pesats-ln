<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('komoditas_entitas', function (Blueprint $table) {
            $table->string('nomor_sertifikat')->nullable()->after('komoditas_id');
        });
    }

    public function down(): void
    {
        Schema::table('komoditas_entitas', function (Blueprint $table) {
            $table->dropColumn('nomor_sertifikat');
        });
    }
};
