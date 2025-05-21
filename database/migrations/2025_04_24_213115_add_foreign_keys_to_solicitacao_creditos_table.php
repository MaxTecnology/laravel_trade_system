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
        Schema::table('solicitacao_creditos', function (Blueprint $table) {
            // Chaves estrangeiras
            $table->foreign('usuario_solicitante_id')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('usuario_criador_id')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('matriz_id')->references('id_usuario')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitacao_creditos', function (Blueprint $table) {
            $table->dropForeignIfExists('solicitacao_creditos_usuario_solicitante_id_foreign');
            // Outras constraints que também precisam ser removidas...
        });
    }
};
