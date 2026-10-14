<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('espacios_comunes', function (Blueprint $table) {
            $table->unsignedInteger('aforo')->nullable();
            $table->time('hora_apertura')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->decimal('costo', 12, 0)->nullable();
            $table->text('reglas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('espacios_comunes', function (Blueprint $table) {
            $table->dropColumn(['aforo', 'hora_apertura', 'hora_cierre', 'costo', 'reglas']);
        });
    }
};
