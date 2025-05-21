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
        Schema::table('imagens', function (Blueprint $table) {
            $table->foreign('oferta_id')->references('id_oferta')->on('ofertas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imagens', function (Blueprint $table) {
            $table->dropForeign(['oferta_id']);
        });
    }
};
