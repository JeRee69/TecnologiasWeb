<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encomiendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('cascade');
            $table->foreignId('conserje_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('empresa_delivery', 100);
            $table->dateTime('fecha_ingreso');
            $table->dateTime('fecha_retiro')->nullable();
            $table->enum('estado', ['Ingresado', 'Notificado', 'En Custodia', 'Retirado'])->default('Ingresado');
            $table->string('codigo_seguimiento', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encomiendas');
    }
};
