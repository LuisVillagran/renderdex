<?php

namespace App\Services\Library;

use App\Enums\MatchSource;
use App\Models\Game;
use App\Services\Steam\OwnedGame;

/**
 * Un juego de la biblioteca de Steam con su página de la wiki, si se encontró.
 */
readonly class LibraryEntry
{
    public function __construct(
        public OwnedGame $owned,
        public ?Game $game,
        public ?MatchSource $matchedBy,
    ) {}
}
