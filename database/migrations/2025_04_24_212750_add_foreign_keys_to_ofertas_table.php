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
        Schema::table('ofertas', function (Blueprint $table) {
            $table->foreign('categoria_id')->references('id_categoria')->on('categorias')->onDelete('set null');
            $table->foreign('sub_categoria_id')->references('id_sub_categoria')->on('sub_categorias')->onDelete('set null');
            $table->foreign('usuario_id')->references('id_usuario')->on('usuarios')->onDelete('set null');
            $table->foreign('sub_conta_id')->references('id_sub_contas')->on('sub_contas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ofertas', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['sub_categoria_id']);
            $table->dropForeign(['usuario_id']);
            $table->dropForeign(['sub_conta_id']);
        });
    }
};
