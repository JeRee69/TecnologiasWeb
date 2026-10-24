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
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->string('periodo', 50);
            $table->unsignedInteger('monto_total');
            $table->json('desglose_json')->nullable();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['Pendiente', 'Pagado', 'Moroso', 'En revisión'])->default('Pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['departamento_id', 'periodo']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_comunes');
    }
};
