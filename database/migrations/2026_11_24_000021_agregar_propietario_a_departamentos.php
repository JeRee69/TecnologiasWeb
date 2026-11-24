<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['nombre' => 'Dueño'],
            [
                'descripcion' => 'Consulta de solo lectura de los departamentos de su propiedad.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Schema::table('departamentos', function (Blueprint $table) {
            $table->foreignId('propietario_id')
                ->nullable()
                ->after('estado')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('propietario_id');
        });

        DB::table('roles')->where('nombre', 'Dueño')->delete();
    }
};
