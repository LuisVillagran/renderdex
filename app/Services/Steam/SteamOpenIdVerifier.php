<?php

namespace App\Services\Steam;

use App\Services\Steam\Exceptions\SteamLoginException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Steam\OpenIDValidationException;
use SocialiteProviders\Steam\Provider as SteamProvider;

/**
 * Verifica la respuesta de "Sign in through Steam" (OpenID 2.0) y devuelve el SteamID64.
 *
 * Antes de consultar a Steam se exige que la respuesta venga de steamcommunity.com, con un
 * claimed_id bien formado y firmado. La validación del paquete es más laxa que esto.
 */
class SteamOpenIdVerifier
{
    private const OP_ENDPOINT = 'https://steamcommunity.com/openid/login';

    private const CLAIMED_ID_PATTERN = '#^https://steamcommunity\.com/openid/id/(7656119\d{10})$#';

    /**
     * @throws SteamLoginException
     */
    public function verify(Request $request): string
    {
        // PHP convierte los puntos de los parámetros en guiones bajos: openid.claimed_id -> openid_claimed_id.
        $signedFields = explode(',', (string) $request->query('openid_signed'));

        $comesFromSteam = $request->query('openid_op_endpoint') === self::OP_ENDPOINT
            && preg_match(self::CLAIMED_ID_PATTERN, (string) $request->query('openid_claimed_id'), $matches) === 1
            && in_array('claimed_id', $signedFields, true)
            && in_array('op_endpoint', $signedFields, true);

        if (! $comesFromSteam) {
            throw new SteamLoginException('The OpenID response is not a valid Steam login.');
        }

        $steamId = $matches[1];

        try {
            /** @var SteamProvider $provider */
            $provider = Socialite::driver('steam')->setRequest($request);
            $isValid = $provider->validate();
        } catch (OpenIDValidationException|GuzzleException) {
            throw new SteamLoginException('Steam could not verify the login.');
        }

        if (! $isValid || (string) $provider->steamId !== $steamId) {
            throw new SteamLoginException('Steam did not confirm the login.');
        }

        return $steamId;
    }
}
