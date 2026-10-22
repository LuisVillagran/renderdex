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
            // Respaldo para cruzar por nombre los juegos a los que la wiki no les puso Steam AppID.
            // Lo completa pcgw:sync; queda null hasta el siguiente sync.
            $table->string('normalized_title')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('normalized_title');
        });
    }
};
