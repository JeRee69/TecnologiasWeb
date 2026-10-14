<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Solo inicializar una base vacía; nunca restablecer claves al reiniciar.
        if (! DB::table('users')->exists() && ! DB::table('roles')->exists()) {
            (new Database\Seeders\DatabaseSeeder)->run();
        }
    }

    public function down(): void
    {
        // Los usuarios pueden tener datos propios: no borrarlos al revertir.
    }
};
