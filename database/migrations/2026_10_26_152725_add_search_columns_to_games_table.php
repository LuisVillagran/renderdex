<?php

use App\Services\SearchKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TECHNOLOGY_COLUMNS = ['dlss', 'dlss_fg', 'dlss_mfg', 'fsr', 'fsr_fg', 'xess', 'xess_fg', 'ray_tracing'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // pg_trgm es una extensión "trusted": la puede crear el dueño de la base, sin superusuario.
        DB::statement('create extension if not exists pg_trgm');

        $hasData = collect(self::TECHNOLOGY_COLUMNS)
            ->map(fn (string $column) => "{$column} <> 'unknown'")
            ->implode(' or ');

        Schema::table('games', function (Blueprint $table) use ($hasData) {
            $table->string('search_title')->default('');
            // Columna generada: siempre coincide con la clasificación, la escriba quien la escriba.
            $table->boolean('has_data')->storedAs("({$hasData})")->index();
        });

        $this->backfillSearchTitles();

        // Acelera tanto el LIKE '%texto%' como la similitud por trigramas.
        DB::statement('create index games_search_title_trgm_index on games using gin (search_title gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop index if exists games_search_title_trgm_index');

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['search_title', 'has_data']);
        });
    }

    /**
     * Calcula la clave de los juegos ya sincronizados, sin esperar al próximo sync.
     */
    private function backfillSearchTitles(): void
    {
        $searchKey = app(SearchKey::class);

        DB::table('games')->select(['id', 'title'])->orderBy('id')->chunk(1000, function ($games) use ($searchKey) {
            $values = $games->map(fn ($game) => [$game->id, $searchKey->make($game->title)]);

            DB::update(
                'update games set search_title = data.search_title from (values '
                .$values->map(fn () => '(?::bigint, ?)')->implode(', ')
                .') as data(id, search_title) where games.id = data.id',
                $values->flatten()->all(),
            );
        });
    }
};
