<?php

namespace App\Services\Steam;

/**
 * Normaliza lo que el usuario pega: URL de perfil, URL personalizada, SteamID64 o vanity sola.
 */
class ProfileInputParser
{
    /** El SteamID64 individual más bajo posible (cuenta 1 del universo público). */
    private const MIN_STEAM_ID = 76561197960265729;

    private const ALLOWED_HOSTS = ['steamcommunity.com', 'www.steamcommunity.com'];

    /** Steam acepta letras, números, guiones y guiones bajos en las URLs personalizadas. */
    private const VANITY_PATTERN = '/^[A-Za-z0-9_-]{2,32}$/';

    /**
     * @throws InvalidProfileInputException
     */
    public function parse(string $input): ProfileInput
    {
        $input = trim($input);

        if ($input === '') {
            throw new InvalidProfileInputException('The profile input is empty.');
        }

        if ($this->looksLikeUrl($input)) {
            return $this->parseUrl($input);
        }

        if ($this->isSteamId($input)) {
            return ProfileInput::steamId($input);
        }

        if (preg_match(self::VANITY_PATTERN, $input) === 1) {
            return ProfileInput::vanity($input);
        }

        throw new InvalidProfileInputException('The profile input is not a Steam profile URL, SteamID64 or custom URL.');
    }

    private function looksLikeUrl(string $input): bool
    {
        return str_contains($input, '/') || str_contains($input, '.');
    }

    private function parseUrl(string $input): ProfileInput
    {
        $url = preg_match('#^https?://#i', $input) === 1 ? $input : "https://{$input}";
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
        $segments = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));

        if (! in_array($host, self::ALLOWED_HOSTS, true) || count($segments) < 2) {
            throw new InvalidProfileInputException('The URL is not a Steam Community profile.');
        }

        [$kind, $value] = $segments;

        return match (true) {
            $kind === 'profiles' && $this->isSteamId($value) => ProfileInput::steamId($value),
            $kind === 'id' && preg_match(self::VANITY_PATTERN, $value) === 1 => ProfileInput::vanity($value),
            default => throw new InvalidProfileInputException('The URL is not a Steam Community profile.'),
        };
    }

    private function isSteamId(string $value): bool
    {
        return preg_match('/^\d{17}$/', $value) === 1 && (int) $value >= self::MIN_STEAM_ID;
    }
}
