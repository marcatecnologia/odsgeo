<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segmentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilha_ods_id')->references('id')->on('planilhas_ods')->onDelete('cascade');
            $table->foreignId('vertice_inicial_id')->constrained('vertices')->onDelete('cascade');
            $table->foreignId('vertice_final_id')->constrained('vertices')->onDelete('cascade');
            $table->decimal('azimute', 10, 6);
            $table->decimal('distancia', 10, 2);
            $table->string('confrontante')->nullable();
            $table->string('tipo_limite')->nullable();
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmentos');
    }
}; 