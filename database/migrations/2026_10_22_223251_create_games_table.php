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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pcgw_page_id')->unique();
            $table->string('title');
            $table->string('dlss', 7)->default('unknown');
            $table->string('dlss_fg', 7)->default('unknown');
            $table->string('dlss_mfg', 7)->default('unknown');
            $table->string('fsr', 7)->default('unknown');
            $table->string('fsr_fg', 7)->default('unknown');
            $table->string('xess', 7)->default('unknown');
            $table->string('xess_fg', 7)->default('unknown');
            $table->string('ray_tracing', 7)->default('unknown');
            $table->text('upscaling_tech')->nullable();
            $table->text('frame_gen_tech')->nullable();
            $table->timestamp('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
