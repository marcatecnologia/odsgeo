<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vertices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilha_ods_id')->constrained()->onDelete('cascade');
            $table->string('nome_ponto');
            $table->decimal('coordenada_x', 15, 6);
            $table->decimal('coordenada_y', 15, 6);
            $table->decimal('altitude', 10, 2)->nullable();
            $table->string('tipo_marco')->nullable();
            $table->string('codigo_sirgas')->nullable();
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vertices');
    }
}; 