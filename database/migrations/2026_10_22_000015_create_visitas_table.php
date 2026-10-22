<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->foreignId('conserje_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre_visitante', 150);
            $table->string('rut_visitante', 20)->nullable()->index();
            $table->dateTime('fecha_ingreso');
            $table->dateTime('fecha_salida')->nullable();
            $table->enum('estado', ['En Recinto', 'Finalizada'])->default('En Recinto')->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};
