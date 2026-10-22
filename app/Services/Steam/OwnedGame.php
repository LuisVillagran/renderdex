<?php

namespace App\Services\Steam;

readonly class OwnedGame
{
    public function __construct(
        public int $appid,
        public string $name,
        public int $playtimeMinutes,
    ) {}

    /**
     * @param  array{appid: int, name?: string, playtime_forever?: int}  $game
     */
    public static function fromApi(array $game): self
    {
        return new self(
            appid: (int) $game['appid'],
            name: (string) ($game['name'] ?? ''),
            playtimeMinutes: (int) ($game['playtime_forever'] ?? 0),
        );
    }

    /**
     * @return array{appid: int, name: string, playtime_forever: int}
     */
    public function toArray(): array
    {
        return [
            'appid' => $this->appid,
            'name' => $this->name,
            'playtime_forever' => $this->playtimeMinutes,
        ];
    }
}
