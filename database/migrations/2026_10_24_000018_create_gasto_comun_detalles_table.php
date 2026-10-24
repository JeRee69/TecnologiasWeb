<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gasto_comun_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gasto_comun_id')->constrained('gastos_comunes')->cascadeOnDelete();
            $table->string('concepto', 200);
            $table->unsignedBigInteger('monto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gasto_comun_detalles');
    }
};
