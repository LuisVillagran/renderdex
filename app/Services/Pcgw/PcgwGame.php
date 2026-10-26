<?php

namespace App\Services\Pcgw;

/**
 * Una fila del join Game + Video de la API Cargo.
 */
readonly class PcgwGame
{
    /**
     * @param  list<int>  $steamAppids
     */
    public function __construct(
        public int $pageId,
        public string $title,
        public array $steamAppids,
        public ?string $upscaling,
        public ?string $frameGen,
        public ?string $rayTracing,
        public ?string $coverUrl = null,
    ) {}

    /**
     * @param  array{PageID: string, Page: string, SteamAppID?: string|null, Cover?: string|null, Upscaling?: string|null, FrameGen?: string|null, RayTracing?: string|null}  $row
     */
    public static function fromCargo(array $row): self
    {
        return new self(
            pageId: (int) $row['PageID'],
            title: $row['Page'],
            steamAppids: self::parseAppids($row['SteamAppID'] ?? null),
            upscaling: self::nullIfBlank($row['Upscaling'] ?? null),
            frameGen: self::nullIfBlank($row['FrameGen'] ?? null),
            rayTracing: self::nullIfBlank($row['RayTracing'] ?? null),
            coverUrl: PcgwCover::normalize($row['Cover'] ?? null),
        );
    }

    /**
     * @return list<int>
     */
    private static function parseAppids(?string $list): array
    {
        return collect(explode(',', (string) $list))
            ->map(fn (string $appid) => trim($appid))
            ->filter(fn (string $appid) => ctype_digit($appid))
            ->map(fn (string $appid) => (int) $appid)
            ->unique()
            ->values()
            ->all();
    }

    private static function nullIfBlank(?string $value): ?string
    {
        return blank($value) ? null : trim($value);
    }
}
