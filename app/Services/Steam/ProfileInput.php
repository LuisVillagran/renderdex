<?php

namespace App\Services\Steam;

/**
 * Perfil normalizado: o un SteamID64 o una URL personalizada (vanity) que hay que resolver.
 */
readonly class ProfileInput
{
    private function __construct(
        public ?string $steamId,
        public ?string $vanity,
    ) {}

    public static function steamId(string $steamId): self
    {
        return new self($steamId, null);
    }

    public static function vanity(string $vanity): self
    {
        return new self(null, $vanity);
    }

    public function needsResolving(): bool
    {
        return $this->steamId === null;
    }
}
