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
        Schema::create('planos', function (Blueprint $table) {
            $table->id('id_plano');
            $table->string('nome_plano');
            $table->string('tipo_do_plano')->nullable();
            $table->string('imagem')->nullable();
            $table->float('taxa_inscricao');
            $table->float('taxa_comissao');
            $table->float('taxa_manutencao_anual');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
