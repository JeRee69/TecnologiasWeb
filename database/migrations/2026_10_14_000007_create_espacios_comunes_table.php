<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espacios_comunes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->text('descripcion');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espacios_comunes');
    }
};
