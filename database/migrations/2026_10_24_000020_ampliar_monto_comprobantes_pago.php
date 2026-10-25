<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobantes_pago', fn (Blueprint $table) => $table->unsignedBigInteger('monto')->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('comprobantes_pago', fn (Blueprint $table) => $table->unsignedInteger('monto')->nullable()->change());
    }
};
