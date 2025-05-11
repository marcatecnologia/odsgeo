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
        Schema::table('planilhas_ods', function (Blueprint $table) {
            $table->string('tipo_documento')->default('cpf')->after('proprietario_nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planilhas_ods', function (Blueprint $table) {
            $table->dropColumn('tipo_documento');
        });
    }
};
