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
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained()->onDelete('cascade');
            $table->string('nome');
            $table->string('tipo'); // georreferenciamento, demarcação, etc
            $table->text('descricao')->nullable();
            $table->string('status')->default('pendente'); // pendente, em_andamento, concluido
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->string('diretorio')->unique(); // Diretório específico para os arquivos do serviço
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
