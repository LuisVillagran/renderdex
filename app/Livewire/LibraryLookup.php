<?php

namespace App\Livewire;

use App\Services\Steam\Exceptions\ProfileNotFoundException;
use App\Services\Steam\Exceptions\SteamUnavailableException;
use App\Services\Steam\InvalidProfileInputException;
use App\Services\Steam\ProfileInputParser;
use App\Services\Steam\SteamClient;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Campo para pegar un perfil de Steam: lo normaliza, lo resuelve y abre la biblioteca.
 */
class LibraryLookup extends Component
{
    public string $profile = '';

    public function lookup(ProfileInputParser $parser, SteamClient $steam): void
    {
        $this->validate(
            ['profile' => ['required', 'string', 'max:255']],
            ['profile.required' => __('lookup.errors.required')],
        );

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
