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
        Schema::create('sats_ln', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('entity_id');          // pemegang izin (company/penangkaran/perorangan/upt)
            $table->unsignedBigInteger('customer_company_id')->nullable();
            $table->enum('type_satsln', ['Export', 'Import', 'Re-Export']);
            $table->unsignedBigInteger('country_of_exportation')->nullable();
            $table->unsignedBigInteger('country_of_destination')->nullable();
            $table->unsignedBigInteger('port_of_exportation')->nullable();
            $table->unsignedBigInteger('port_of_destination')->nullable();
            $table->unsignedBigInteger('purpose_of_transaction_id');
            $table->date('publish_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedBigInteger('verified_id')->nullable();
            $table->unsignedBigInteger('issued_by_id')->nullable();
            $table->unsignedBigInteger('printed_user_id')->nullable();
            $table->text('special_conditions')->nullable();
            $table->enum('satsln_issued_type', ['P_0', 'P_1', 'P_2', 'P_3']);
            $table->string('satsln_number_issued')->unique();  // mis. 2600001
            $table->smallInteger('year');                       // kuota nasional tahun berapa
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('entity_id')->references('id')->on('entities')->restrictOnDelete();
            $table->foreign('customer_company_id')->references('id')->on('customer_companies')->nullOnDelete();
            $table->foreign('purpose_of_transaction_id')->references('id')->on('purpose_of_transactions')->restrictOnDelete();
            $table->foreign('verified_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('issued_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('printed_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('entity_id');
            $table->index('type_satsln');
            $table->index('satsln_issued_type');
            $table->index('publish_date');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sats_ln');
    }
};
