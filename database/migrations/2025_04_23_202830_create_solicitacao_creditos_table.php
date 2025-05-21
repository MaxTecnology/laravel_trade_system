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
        Schema::create('solicitacao_creditos', function (Blueprint $table) {
            $table->id('id_solicitacao_credito');
            $table->float('valor_solicitado');
            $table->string('status'); // Pode ser 'Pendente', 'Aprovado', 'Negado', etc.
            $table->string('motivo_rejeicao')->nullable();
            $table->unsignedBigInteger('usuario_solicitante_id');
            $table->text('descricao_solicitante')->nullable();
            $table->text('comentario_agencia')->nullable();
            $table->boolean('matriz_aprovacao')->nullable();
            $table->text('comentario_matriz')->nullable();
            $table->unsignedBigInteger('usuario_criador_id');
            $table->unsignedBigInteger('matriz_id')->nullable();
            $table->timestamps();

            // Chaves estrangeiras separadas
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitacao_creditos');
    }
};
