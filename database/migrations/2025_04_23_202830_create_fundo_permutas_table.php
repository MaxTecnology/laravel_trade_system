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
        Schema::create('fundo_permutas', function (Blueprint $table) {
            $table->id('id_fundo_permuta');
            $table->float('valor');
            $table->unsignedBigInteger('usuario_id');
            $table->string('descricao', 255)->nullable(); // Adicionado o campo descricao
            $table->timestamps();

            // Chave estrangeira (assumindo que a tabela usuarios já foi criada)
            $table->foreign('usuario_id')->references('id_usuario')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundo_permutas');
    }
};
