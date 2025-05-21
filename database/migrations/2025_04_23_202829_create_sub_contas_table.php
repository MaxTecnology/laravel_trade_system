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
        Schema::create('sub_contas', function (Blueprint $table) {
            $table->id('id_sub_contas');
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('cpf')->unique();
            $table->string('numero_sub_conta')->unique();
            $table->string('senha');
            $table->string('imagem')->nullable();
            $table->boolean('status_conta')->default(true)->nullable();
            $table->float('reputacao')->default(0.0)->nullable();
            $table->string('telefone')->nullable();
            $table->string('celular')->nullable();
            $table->string('email_contato')->nullable();
            $table->string('logradouro')->nullable();
            $table->integer('numero')->nullable();
            $table->string('cep')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();
            $table->unsignedBigInteger('conta_pai_id');
            $table->json('permissoes')->default('[]');
            $table->string('token_reset_senha')->nullable();
            $table->timestamps();

            $table->foreign('conta_pai_id')->references('id_conta')->on('contas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_contas');
    }
};
