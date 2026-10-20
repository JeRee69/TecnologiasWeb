<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('cancelada_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservas', fn (Blueprint $table) => $table->dropColumn(['motivo_cancelacion', 'cancelada_at']));
    }
};
