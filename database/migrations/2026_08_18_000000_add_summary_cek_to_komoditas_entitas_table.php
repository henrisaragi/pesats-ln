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
            $table->jsonb('summary_cek')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('komoditas_entitas', function (Blueprint $table) {
            $table->dropColumn('summary_cek');
        });
    }
};
