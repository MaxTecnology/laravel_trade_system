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
        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id('id_cobranca');
            $table->float('valor_fatura');
            $table->string('referencia');
            $table->timestamp('created_at')->useCurrent();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('transacao_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('conta_id')->nullable();
            $table->timestamp('vencimento_fatura')->nullable();
            $table->unsignedBigInteger('sub_conta_id')->nullable();
            $table->unsignedBigInteger('gerente_conta_id')->nullable();

            // Chaves estrangeiras (assumindo que as tabelas relacionadas já foram criadas)
            $table->foreign('transacao_id')->references('id_transacao')->on('transacoes')->onDelete('set null');
            $table->foreign('usuario_id')->references('id_usuario')->on('usuarios')->onDelete('set null');
            $table->foreign('conta_id')->references('id_conta')->on('contas')->onDelete('set null');
            $table->foreign('sub_conta_id')->references('id_sub_contas')->on('sub_contas')->onDelete('set null');
            $table->foreign('gerente_conta_id')->references('id_usuario')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobrancas');
    }
};

