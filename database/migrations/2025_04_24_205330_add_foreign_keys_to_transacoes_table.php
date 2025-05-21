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
        Schema::table('transacoes', function (Blueprint $table) {
            // Chaves estrangeiras
            $table->foreign('oferta_id')->references('id_oferta')->on('ofertas')->onDelete('set null');
            $table->foreign('comprador_id')->references('id_usuario')->on('usuarios')->onDelete('set null');
            $table->foreign('vendedor_id')->references('id_usuario')->on('usuarios')->onDelete('set null');
            $table->foreign('sub_conta_comprador_id')->references('id_sub_contas')->on('sub_contas')->onDelete('set null');
            $table->foreign('sub_conta_vendedor_id')->references('id_sub_contas')->on('sub_contas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table) {
            // Remover as chaves estrangeiras
            $table->dropForeign(['oferta_id']);
            $table->dropForeign(['comprador_id']);
            $table->dropForeign(['vendedor_id']);
            $table->dropForeign(['sub_conta_comprador_id']);
            $table->dropForeign(['sub_conta_vendedor_id']);
        });
    }
};
