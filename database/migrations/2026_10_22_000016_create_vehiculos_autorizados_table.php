<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos_autorizados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->foreignId('conserje_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('patente', 10)->index();
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 60)->nullable();
            $table->string('color', 40)->nullable();
            $table->enum('tipo', ['Residente', 'Visita Frecuente', 'Servicio'])->default('Residente');
            $table->enum('estado', ['Autorizado', 'Revocado'])->default('Autorizado')->index();
            $table->date('vigente_hasta')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos_autorizados');
    }
};
