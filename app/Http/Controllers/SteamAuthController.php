<?php

namespace App\Http\Controllers;

use App\Services\Steam\Exceptions\SteamLoginException;
use App\Services\Steam\Exceptions\SteamUnavailableException;
use App\Services\Steam\SteamClient;
use App\Services\Steam\SteamOpenIdVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SteamAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('steam')->redirect();
    }

    public function callback(Request $request, SteamOpenIdVerifier $verifier, SteamClient $steam): RedirectResponse
    {
        if ($request->query('openid_mode') === 'cancel') {
            return to_route('home')->with('error', __('auth.steam.cancelled'));
        }

        try {
            $steamId = $verifier->verify($request);
        } catch (SteamLoginException) {
            return to_route('home')->with('error', __('auth.steam.failed'));
        }

        // Sin nombre ni avatar se puede seguir: el login solo sirve para identificar el SteamID.
        try {
            $summary = $steam->playerSummary($steamId);
        } catch (SteamUnavailableException) {
            $summary = null;
        }

        $request->session()->regenerate();
        $request->session()->put('steam_user', [
            'steam_id' => $steamId,
            'name' => $summary?->name,
            'avatar' => $summary?->avatarUrl,
        ]);

        return to_route('library.show', $steamId);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
