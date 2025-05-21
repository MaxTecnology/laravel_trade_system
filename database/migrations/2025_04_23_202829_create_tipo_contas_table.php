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
        Schema::create('tipo_contas', function (Blueprint $table) {
            $table->id('id_tipo_conta');
            $table->string('tipo_da_conta');
            $table->string('prefixo_conta');
            $table->string('descricao');
            $table->json('permissoes')->default('[]');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_contas');
    }
};
