<?php

namespace App\Services\Pcgw;

use App\Enums\ChangeType;
use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;
use App\Models\GameChange;
use App\Models\GameSteamAppid;
use App\Services\FeatureClassifier;
use App\Services\SearchKey;
use App\Services\TitleNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Guarda un lote de juegos de PCGamingWiki con su clasificación ya calculada.
 */
class PcgwGameImporter
{
    public function __construct(
        private FeatureClassifier $classifier,
        private TitleNormalizer $normalizer,
        private SearchKey $searchKey,
    ) {}

    /**
     * @param  list<PcgwGame>  $games
     */
    public function import(array $games, CarbonInterface $syncedAt): void
    {
        if ($games === []) {
            return;
        }

        // Una página puede repetirse si la wiki tiene filas duplicadas; gana la última.
        $games = collect($games)->keyBy('pageId');

        DB::transaction(function () use ($games, $syncedAt) {
            $rows = $games->map(fn (PcgwGame $game) => $this->gameRow($game, $syncedAt));

            // Antes del upsert, para comparar con lo que había. Las páginas nuevas no cuentan: así el
            // sync inicial no llena las novedades con decenas de miles de juegos.
            $changes = $this->changes(Game::whereIn('pcgw_page_id', $games->keys())->get()->keyBy('pcgw_page_id'), $rows, $syncedAt);

            Game::upsert(
                $rows->values()->all(),
                uniqueBy: ['pcgw_page_id'],
                update: ['title', 'normalized_title', 'search_title', 'cover_url', 'dlss', 'dlss_fg', 'dlss_mfg', 'fsr', 'fsr_fg', 'xess', 'xess_fg', 'ray_tracing', 'upscaling_tech', 'frame_gen_tech', 'synced_at'],
            );

            $gameIds = Game::whereIn('pcgw_page_id', $games->keys())->pluck('id', 'pcgw_page_id');

            GameSteamAppid::whereIn('game_id', $gameIds)->delete();

            // Si un AppID aparece en varias páginas, queda asociado a la última sincronizada.
            $appidRows = $games
                ->flatMap(fn (PcgwGame $game) => collect($game->steamAppids)->map(fn (int $appid) => [
                    'steam_appid' => $appid,
                    'game_id' => $gameIds[$game->pageId],
                ]))
                ->keyBy('steam_appid')
                ->values()
                ->all();

            GameSteamAppid::upsert($appidRows, uniqueBy: ['steam_appid'], update: ['game_id']);

            GameChange::insert($changes);
        });
    }

    /**
     * Qué tecnologías se agregaron, se quitaron o subieron de versión en los juegos que ya existían.
     *
     * @param  Collection<int, Game>  $existing  Indexado por pcgw_page_id.
     * @param  Collection<int, array<string, string|int|null>>  $rows  Filas nuevas, indexadas por pcgw_page_id.
     * @return list<array<string, string|int|null>>
     */
    private function changes(Collection $existing, Collection $rows, CarbonInterface $syncedAt): array
    {
        return $rows
            ->filter(fn (array $row, int $pageId) => $existing->has($pageId))
            ->flatMap(function (array $row, int $pageId) use ($existing, $syncedAt) {
                $game = $existing[$pageId];

                return collect(Technology::cases())->map(function (Technology $technology) use ($game, $row, $syncedAt) {
                    $before = $game->{$technology->value};
                    $after = Support::from($row[$technology->value]);
                    $previousVersion = $this->classifier->latestVersion($game->upscaling_tech, $technology);
                    $version = $this->classifier->latestVersion($row['upscaling_tech'], $technology);

                    $type = match (true) {
                        $before !== Support::Yes && $after === Support::Yes => ChangeType::Added,
                        $before === Support::Yes && $after !== Support::Yes => ChangeType::Removed,
                        $after === Support::Yes && $previousVersion !== null && $version !== null && version_compare($version, $previousVersion, '>') => ChangeType::Upgraded,
                        default => null,
                    };

                    return $type === null ? null : [
                        'game_id' => $game->id,
                        'technology' => $technology->value,
                        'type' => $type->value,
                        'previous_version' => $type === ChangeType::Added ? null : $previousVersion,
                        'version' => $type === ChangeType::Removed ? null : $version,
                        'detected_at' => $syncedAt->toDateTimeString(),
                    ];
                })->filter();
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string|int|null>
     */
    private function gameRow(PcgwGame $game, CarbonInterface $syncedAt): array
    {
        // upsert() no aplica los casts del modelo: los enums se guardan por su valor.
        $support = collect($this->classifier->classify($game->upscaling, $game->frameGen, $game->rayTracing))
            ->map(fn ($support) => $support->value)
            ->all();

        return [
            'pcgw_page_id' => $game->pageId,
            'title' => $game->title,
            'normalized_title' => $this->normalizer->normalize($game->title),
            'search_title' => $this->searchKey->make($game->title),
            'cover_url' => $game->coverUrl,
            ...$support,
            'upscaling_tech' => $game->upscaling,
            'frame_gen_tech' => $game->frameGen,
            'synced_at' => $syncedAt->toDateTimeString(),
        ];
    }
}
