<?php

namespace App\Services\Steam;

readonly class PlayerSummary
{
    public function __construct(
        public string $steamId,
        public string $name,
        public ?string $avatarUrl,
    ) {}
}
