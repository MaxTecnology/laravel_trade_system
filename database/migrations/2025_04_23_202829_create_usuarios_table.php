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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->unsignedBigInteger('usuario_criador_id')->nullable();
            $table->unsignedBigInteger('matriz_id')->nullable();
            $table->string('nome');
            $table->string('cpf')->unique();
            $table->string('email')->unique();
            $table->string('senha');
            $table->string('imagem')->nullable();
            $table->boolean('status_conta')->default(true)->nullable();
            $table->float('reputacao')->default(0.0)->nullable();
            $table->string('razao_social')->nullable();
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('insc_estadual')->nullable();
            $table->string('insc_municipal')->nullable();
            $table->boolean('mostrar_no_site')->default(true);
            $table->text('descricao')->nullable();
            $table->string('tipo')->nullable();
            $table->string('tipo_de_moeda')->nullable();
            $table->boolean('status')->default(false);
            $table->string('restricao')->nullable();
            $table->string('nome_contato')->nullable();
            $table->string('telefone')->nullable();
            $table->string('celular')->nullable();
            $table->string('email_contato')->nullable();
            $table->string('email_secundario')->nullable();
            $table->string('site')->nullable();
            $table->string('logradouro')->nullable();
            $table->integer('numero')->nullable();
            $table->string('cep')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();
            $table->string('regiao')->nullable();
            $table->boolean('aceita_orcamento');
            $table->boolean('aceita_voucher');
            $table->integer('tipo_operacao');
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('sub_categoria_id')->nullable();
            $table->integer('taxa_comissao_gerente')->nullable();
            $table->text('permissoes_do_usuario')->default('[]');
            $table->boolean('bloqueado')->default(false);
            $table->string('token_reset_senha')->nullable();
            $table->timestamps();

            // As chaves estrangeiras devem ser adicionadas depois que todas as tabelas existirem
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
