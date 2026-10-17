<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departamento_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('es_titular')->default(false);
            $table->timestamps();

            $table->unique(['departamento_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departamento_user');
    }
};
