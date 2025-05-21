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
        // Criar a extensão uuid-ossp se ela ainda não existir
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        Schema::create('transacoes', function (Blueprint $table) {
            $table->id('id_transacao');
            $table->uuid('codigo')->default(DB::raw('uuid_generate_v4()'));
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('data_do_estorno')->nullable();
            $table->string('nome_comprador');
            $table->string('nome_vendedor');
            $table->unsignedBigInteger('comprador_id')->nullable();
            $table->unsignedBigInteger('vendedor_id')->nullable();
            $table->string('saldo_utilizado');
            $table->float('valor_rt');
            $table->float('valor_adicional');
            $table->float('saldo_anterior_comprador');
            $table->float('saldo_apos_comprador');
            $table->float('saldo_anterior_vendedor');
            $table->float('saldoAposVendedor');
            $table->float('limiteCreditoAnteriorComprador')->nullable();
            $table->float('limiteCreditoAposComprador')->nullable();
            $table->integer('numeroParcelas');
            $table->string('descricao');
            $table->integer('notaAtendimento');
            $table->string('observacaoNota');
            $table->string('status');
            $table->boolean('emiteVoucher')->default(false);
            $table->unsignedBigInteger('oferta_id')->nullable();
            $table->unsignedBigInteger('sub_conta_comprador_id')->nullable();
            $table->unsignedBigInteger('sub_conta_vendedor_id')->nullable();
            $table->float('comissao');
            $table->float('comissaoParcelada');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacoes');

        // Não remova a extensão no down() pois outras tabelas podem estar usando
        // Se realmente quiser remover, use:
        // DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp";');
    }
};
