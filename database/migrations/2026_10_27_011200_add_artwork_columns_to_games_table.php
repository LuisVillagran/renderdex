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
        Schema::table('games', function (Blueprint $table) {
            // URLs del arte de la tienda de Steam (cover, cover_2x, header, hero). Los juegos nuevos
            // lo tienen en rutas con hash que solo se conocen preguntando a Steam (covers:sync).
            $table->jsonb('steam_artwork')->nullable();
            $table->timestamp('steam_artwork_synced_at')->nullable()->index();

            // Portada vertical de SteamGridDB: respaldo para lo que Steam no cubre.
            $table->text('grid_cover_url')->nullable();
            $table->text('grid_cover_thumb_url')->nullable();
            $table->timestamp('grid_checked_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['steam_artwork', 'steam_artwork_synced_at', 'grid_cover_url', 'grid_cover_thumb_url', 'grid_checked_at']);
        });
    }
};
