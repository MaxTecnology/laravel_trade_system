<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id('id_voucher');
            $table->string('codigo', 255)->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent();
            $table->timestamp('data_cancelamento')->nullable();
            $table->unsignedBigInteger('transacao_id');
            $table->string('status')->default('Ativo');

            // Chave estrangeira (assumindo que a tabela transacoes já foi criada)
            $table->foreign('transacao_id')->references('id_transacao')->on('transacoes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
