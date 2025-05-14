<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinates', function (Blueprint $table) {
            $table->id();
            $table->string('point');
            $table->string('description')->nullable();
            $table->decimal('utm_north', 15, 6)->nullable();
            $table->decimal('utm_east', 15, 6)->nullable();
            $table->decimal('latitude_decimal', 15, 6)->nullable();
            $table->decimal('longitude_decimal', 15, 6)->nullable();
            $table->string('latitude_gms')->nullable();
            $table->string('longitude_gms')->nullable();
            $table->decimal('elevation', 10, 2)->nullable();
            $table->string('datum');
            $table->integer('utm_zone')->nullable();
            $table->string('central_meridian')->nullable();
            $table->foreignId('service_id')->constrained('servicos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinates');
    }
}; 