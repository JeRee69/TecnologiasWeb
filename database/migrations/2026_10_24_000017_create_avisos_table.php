<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('autor_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo', 150);
            $table->text('contenido');
            $table->enum('prioridad', ['Baja', 'Media', 'Alta', 'Urgente'])->default('Media')->index();
            $table->enum('estado', ['Publicado', 'Retirado'])->default('Publicado')->index();
            $table->dateTime('fecha_publicacion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
