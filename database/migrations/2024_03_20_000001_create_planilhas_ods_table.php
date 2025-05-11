<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilhas_ods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servico_id')->constrained()->onDelete('cascade');
            $table->string('nome_imovel');
            $table->string('municipio');
            $table->string('uf', 2);
            $table->string('codigo_imovel')->nullable();
            $table->string('tipo_imovel');
            $table->decimal('area_imovel', 10, 2);
            
            // Dados do Responsável Técnico
            $table->string('rt_nome');
            $table->string('rt_cpf', 14);
            $table->string('rt_crea_cau');
            $table->string('rt_telefone');
            $table->string('rt_email');
            
            // Dados do Proprietário
            $table->string('proprietario_nome');
            $table->string('proprietario_cpf_cnpj');
            $table->text('proprietario_endereco');
            $table->decimal('proprietario_percentual', 5, 2);
            
            // Dados Adicionais
            $table->date('data_medicao');
            $table->string('metodo_utilizado');
            $table->string('tipo_equipamento');
            $table->text('observacoes')->nullable();
            
            $table->string('arquivo_ods')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilhas_ods');
    }
}; 