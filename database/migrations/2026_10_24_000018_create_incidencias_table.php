<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->enum('categoria', [
                'Espacios Comunes',
                'Ascensores',
                'Seguridad',
                'Limpieza',
                'Ruidos Molestos',
                'Otro',
            ])->index();
            $table->string('titulo', 150);
            $table->text('descripcion');
            $table->enum('estado', ['Pendiente', 'En Proceso', 'Resuelta', 'Rechazada'])->default('Pendiente')->index();
            $table->foreignId('personal_resolucion_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('respuesta_administracion')->nullable();
            $table->dateTime('fecha_resolucion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
