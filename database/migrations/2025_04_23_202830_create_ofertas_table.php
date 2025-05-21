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
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id('id_oferta');
            $table->timestamp('created_at')->useCurrent();
            $table->integer('id_franquia')->nullable();
            $table->string('nome_franquia')->nullable();
            $table->string('titulo');
            $table->string('tipo');
            $table->boolean('status');
            $table->text('descricao');
            $table->integer('quantidade');
            $table->float('valor');
            $table->float('limite_compra');
            $table->dateTime('vencimento');
            $table->string('cidade');
            $table->string('estado');
            $table->string('retirada');
            $table->text('obs');
            $table->json('imagens')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('nome_usuario');
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('sub_categoria_id')->nullable();
            $table->unsignedBigInteger('sub_conta_id')->nullable();

            // Chaves estrangeiras separadas
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ofertas');
    }
};
