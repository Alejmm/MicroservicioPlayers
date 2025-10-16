<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                 // Nombre completo o display name
            $table->unsignedSmallInteger('number')->nullable();   // Dorsal
            $table->string('position', 50)->nullable();  // Posición (PG, SG, etc.)
            $table->unsignedBigInteger('team_id')->nullable();    // Id del equipo (no FK, otro microservicio)
            $table->string('photo_url', 255)->nullable();         // Foto opcional
            $table->timestamps();

            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
