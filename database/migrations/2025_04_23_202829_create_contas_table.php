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
        Schema::create('contas', function (Blueprint $table) {
            $table->id('id_conta');
            $table->integer('taxa_repasse_matriz')->nullable();
            $table->float('limite_credito')->default(0.0);
            $table->float('limite_utilizado')->default(0.0);
            $table->float('limite_disponivel')->nullable();
            $table->float('saldo_permuta')->default(0.0);
            $table->float('saldo_dinheiro')->default(0.0);
            $table->float('limite_venda_mensal');
            $table->float('limite_venda_total');
            $table->float('limite_venda_empresa');
            $table->float('valor_venda_mensal_atual')->default(0.0);
            $table->float('valor_venda_total_atual')->default(0.0);
            $table->integer('dia_fechamento_fatura');
            $table->integer('data_vencimento_fatura');
            $table->string('numero_conta')->unique();
            $table->date('data_de_afiliacao')->nullable();
            $table->string('nome_franquia')->nullable();
            $table->unsignedBigInteger('tipo_conta_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable()->unique();
            $table->unsignedBigInteger('plano_id')->nullable();
            $table->unsignedBigInteger('gerente_conta_id')->nullable();
            $table->json('permissoes_especificas')->nullable()->default('[]');
            $table->timestamps();

            // As chaves estrangeiras serão adicionadas em uma migration separada
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contas');
    }
};
