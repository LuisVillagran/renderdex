<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_steam_appids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('steam_appid')->unique();
            // PostgreSQL no indexa las claves foráneas por su cuenta, y el sync reemplaza los AppIDs de cada juego.
            $table->foreignId('game_id')->index()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_steam_appids');
    }
};
