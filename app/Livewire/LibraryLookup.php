<?php

namespace App\Livewire;

use App\Services\Steam\Exceptions\ProfileNotFoundException;
use App\Services\Steam\Exceptions\SteamUnavailableException;
use App\Services\Steam\InvalidProfileInputException;
use App\Services\Steam\ProfileInputParser;
use App\Services\Steam\SteamClient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * Campo para pegar un perfil de Steam: lo normaliza, lo resuelve y abre la biblioteca.
 */
class LibraryLookup extends Component
{
    /** Búsquedas por IP y minuto: cada una puede costar una llamada a ResolveVanityURL. */
    public const LOOKUPS_PER_MINUTE = 10;

    public string $profile = '';

    public function lookup(ProfileInputParser $parser, SteamClient $steam): void
    {
        $this->validate(
            ['profile' => ['required', 'string', 'max:255']],
            ['profile.required' => __('lookup.errors.required')],
        );

        // La IP va hasheada: la clave queda en la tabla de caché mientras dura el límite.
        $rateLimitKey = 'profile-lookup:'.sha1((string) request()->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::LOOKUPS_PER_MINUTE)) {
            $this->addError('profile', __('lookup.errors.too_many', ['seconds' => RateLimiter::availableIn($rateLimitKey)]));

            return;
        }

        RateLimiter::increment($rateLimitKey);

        try {
            $steamId = $steam->resolveSteamId($parser->parse($this->profile));
        } catch (InvalidProfileInputException) {
            $this->addError('profile', __('lookup.errors.invalid'));

            return;
        } catch (ProfileNotFoundException) {
            $this->addError('profile', __('lookup.errors.not_found'));

            return;
        } catch (SteamUnavailableException) {
            $this->addError('profile', __('lookup.errors.unavailable'));

            return;
        }

        $this->redirectRoute('library.show', ['steamId' => $steamId]);
    }

    public function render(): View
    {
        return view('livewire.library-lookup');
    }
}
