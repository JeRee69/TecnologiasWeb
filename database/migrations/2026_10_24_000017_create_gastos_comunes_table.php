<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_comunes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->restrictOnDelete();
            $table->date('periodo');
            $table->unsignedBigInteger('monto');
            $table->string('estado', 30)->default('pendiente');
            $table->timestamps();
            $table->unique(['departamento_id', 'periodo']);
            $table->index('periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_comunes');
    }
};
