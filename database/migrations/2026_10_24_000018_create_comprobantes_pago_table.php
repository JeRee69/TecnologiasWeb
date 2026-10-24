<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gasto_comun_id')->constrained('gastos_comunes')->cascadeOnDelete();
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('archivo_path');
            $table->string('archivo_nombre_original');
            $table->string('archivo_mime', 100);
            $table->unsignedInteger('archivo_tamano');
            $table->unsignedInteger('monto')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->enum('estado', ['en_revision', 'aprobado', 'rechazado'])->default('en_revision');
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();

            $table->index(['gasto_comun_id', 'estado']);
            $table->index('departamento_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes_pago');
    }
};
