<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_camadas', function (Blueprint $table) {
            $table->id();
            $table->string('uf', 2);
            $table->string('tipo');
            $table->string('tema');
            $table->string('url');
            $table->boolean('ativo')->default(true);
            $table->text('descricao')->nullable();
            $table->timestamp('data_sync')->nullable();
            $table->timestamps();

            $table->unique(['uf', 'tipo']);
            $table->index(['uf', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_camadas');
    }
}; 