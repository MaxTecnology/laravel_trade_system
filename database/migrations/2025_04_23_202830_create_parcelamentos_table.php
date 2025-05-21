<?php

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
        Schema::create('parcelamentos', function (Blueprint $table) {
            $table->id('id_parcelamento');
            $table->integer('numero_parcela');
            $table->float('valor_parcela');
            $table->float('comissao_parcela');
            $table->unsignedBigInteger('transacao_id');
            $table->timestamps();

            // Chave estrangeira (assumindo que a tabela transacoes já foi criada)
            $table->foreign('transacao_id')->references('id_transacao')->on('transacoes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcelamentos');
    }
};
