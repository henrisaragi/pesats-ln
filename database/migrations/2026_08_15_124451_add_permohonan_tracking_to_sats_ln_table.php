<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->unsignedBigInteger('permohonan_id')->nullable()->after('id');
            $table->timestamp('printed_at')->nullable()->after('printed_user_id');
            $table->timestamp('signed_at')->nullable()->after('issued_by_id');

            $table->foreign('permohonan_id')->references('id')->on('permohonan')->cascadeOnDelete();
            $table->index('permohonan_id');
        });
    }

    public function down(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->dropForeign(['permohonan_id']);
            $table->dropColumn(['permohonan_id', 'printed_at', 'signed_at']);
        });
    }
};
