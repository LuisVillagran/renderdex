<?php

namespace App\Services\Pcgw;

use App\Models\Game;
use App\Models\GameSteamAppid;
use App\Services\FeatureClassifier;
use App\Services\TitleNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Guarda un lote de juegos de PCGamingWiki con su clasificación ya calculada.
 */
class PcgwGameImporter
{
    public function __construct(
        private FeatureClassifier $classifier,
        private TitleNormalizer $normalizer,
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
            Game::upsert(
                $games->map(fn (PcgwGame $game) => $this->gameRow($game, $syncedAt))->values()->all(),
                uniqueBy: ['pcgw_page_id'],
                update: ['title', 'normalized_title', 'dlss', 'dlss_fg', 'dlss_mfg', 'fsr', 'fsr_fg', 'xess', 'xess_fg', 'ray_tracing', 'upscaling_tech', 'frame_gen_tech', 'synced_at'],
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
        });
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
            ...$support,
            'upscaling_tech' => $game->upscaling,
            'frame_gen_tech' => $game->frameGen,
            'synced_at' => $syncedAt->toDateTimeString(),
        ];
    }
}
