<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'confirmada', 'rechazada', 'cancelada'])->default('pendiente')->change();
        });
        Schema::create('reserva_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // No perder rechazos existentes al volver al enum anterior.
        if (\Illuminate\Support\Facades\DB::table('reservas')->where('estado', 'rechazada')->exists()) {
            throw new RuntimeException('No se puede revertir la revisión mientras existan reservas rechazadas.');
        }
        Schema::dropIfExists('reserva_eventos');
        Schema::table('reservas', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'confirmada', 'cancelada'])->default('pendiente')->change();
        });
    }
};
