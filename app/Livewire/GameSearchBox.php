<?php

namespace App\Livewire;

use App\Models\Game;
use App\Services\Catalog\CatalogSearch;
use App\Services\SupportSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Buscador con vista previa: muestra los mejores resultados del catálogo mientras se escribe.
 * Es un formulario GET hacia /juegos, así que sin JavaScript (o con Enter) sigue funcionando.
 */
class GameSearchBox extends Component
{
    public const MIN_LENGTH = 2;

    /** Consultas por IP y minuto: con debounce, alcanza para tipear rápido varias búsquedas. */
    public const SUGGESTIONS_PER_MINUTE = 60;

    public string $query = '';

    /** Versión chica para el header. */
    #[Locked]
    public bool $compact = false;

    /** Prefijo de los IDs, para poder tener más de un buscador en la misma página. */
    #[Locked]
    public string $inputId = 'game-search';

    /** Texto del label; por defecto, "Buscar un juego". */
    #[Locked]
    public ?string $label = null;

    public function render(CatalogSearch $catalog, SupportSummary $summary): View
    {
        $throttled = false;
        $suggestions = collect();

        if (mb_strlen(trim($this->query)) >= self::MIN_LENGTH) {
            // La IP va hasheada, igual que en LibraryLookup.
            $rateLimitKey = 'game-suggestions:'.sha1((string) request()->ip());

            if (RateLimiter::tooManyAttempts($rateLimitKey, self::SUGGESTIONS_PER_MINUTE)) {
                $throttled = true;
            } else {
                RateLimiter::increment($rateLimitKey);

                $suggestions = $catalog->suggest($this->query)->map(fn (Game $game) => [
                    'url' => $game->url(),
                    'title' => $game->title,
                    'appid' => $game->primarySteamAppid(),
                    'hasData' => $game->has_data,
                    'supported' => $summary->labels($game),
                ]);
            }
        }

        return view('livewire.game-search-box', [
            'suggestions' => $suggestions,
            'throttled' => $throttled,
            'searching' => mb_strlen(trim($this->query)) >= self::MIN_LENGTH,
        ]);
    }
}
