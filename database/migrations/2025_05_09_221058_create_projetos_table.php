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
        Schema::create('projetos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->string('nome');
            $table->string('descricao')->nullable();
            $table->string('status')->default('ativo'); // ativo, concluído, cancelado
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->string('diretorio')->unique(); // Diretório específico para os arquivos do projeto
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projetos');
    }
};
