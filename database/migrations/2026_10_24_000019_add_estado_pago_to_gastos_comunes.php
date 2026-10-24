<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_comunes', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->index();
            $table->timestamp('pago_validado_at')->nullable();
            $table->foreignId('pago_validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referencia_pago', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gastos_comunes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pago_validado_por');
            $table->dropIndex(['fecha_vencimiento']);
            $table->dropColumn(['fecha_vencimiento', 'pago_validado_at', 'referencia_pago']);
        });
    }
};
