<?php

namespace App\Services\Pcgw;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Throwable;

class PcgwClient
{
    private CookieJar $cookies;

    private bool $loggedIn = false;

    /** @var array{upscaling: string, frame_gen: string, ray_tracing: string}|null */
    private ?array $videoFields = null;

    public function __construct()
    {
        $this->cookies = new CookieJar;
    }

    /**
     * Devuelve una página del join Game + Video, ordenada por ID de página.
     *
     * @return list<PcgwGame>
     */
    public function games(int $offset, int $limit): array
    {
        $game = config('pcgw.tables.game');
        $video = config('pcgw.tables.video');
        $fields = $this->videoFields();

        $response = $this->cargo([
            'action' => 'cargoquery',
            'tables' => "{$game},{$video}",
            'join_on' => "{$game}._pageID={$video}._pageID",
            'fields' => implode(',', [
                "{$game}._pageID=PageID",
                "{$game}._pageName=Page",
                "{$game}.Steam_AppID=SteamAppID",
                "{$video}.{$fields['upscaling']}=Upscaling",
                "{$video}.{$fields['frame_gen']}=FrameGen",
                "{$video}.{$fields['ray_tracing']}=RayTracing",
            ]),
            'order_by' => "{$game}._pageID",
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return array_map(
            fn (array $item) => PcgwGame::fromCargo($item['title']),
            $response['cargoquery'] ?? [],
        );
    }

    /**
     * Nombres reales de los campos de Video, detectados con action=cargofields.
     *
     * @return array{upscaling: string, frame_gen: string, ray_tracing: string}
     */
    public function videoFields(): array
    {
        if ($this->videoFields !== null) {
            return $this->videoFields;
        }

        // Un error de la API aquí no es grave: se usan los nombres de config/pcgw.php.
        // Los errores de login o de conexión sí se propagan.
        $available = array_keys($this->cargo([
            'action' => 'cargofields',
            'table' => config('pcgw.tables.video'),
        ], throwOnApiError: false)['cargofields'] ?? []);

        /** @var array{upscaling: string, frame_gen: string, ray_tracing: string} $fields */
        $fields = collect(config('pcgw.video_fields'))
            ->map(fn (array $candidates) => collect($candidates)->first(
                fn (string $candidate) => in_array(mb_strtolower($candidate), array_map('mb_strtolower', $available), true),
                $candidates[0],
            ))
            ->all();

        return $this->videoFields = $fields;
    }

    /**
     * Ejecuta una acción que exige sesión. Si la sesión expiró, inicia sesión otra vez una sola vez.
     *
     * @param  array<string, string|int>  $params
     * @return array<string, mixed>
     */
    private function cargo(array $params, bool $throwOnApiError = true): array
    {
        if (! $this->loggedIn) {
            $this->login();
        }

        $response = $this->send($params);

        if (($response['error']['code'] ?? null) === 'permissiondenied') {
            $this->login();
            $response = $this->send($params);
        }

        if (isset($response['error']) && $throwOnApiError) {
            throw new PcgwException("PCGamingWiki API error [{$response['error']['code']}]: ".($response['error']['info'] ?? ''));
        }

        return $response;
    }

    private function login(): void
    {
        $username = config('pcgw.bot_username');
        $password = config('pcgw.bot_password');

        if (blank($username) || blank($password)) {
            throw new PcgwException('PCGamingWiki bot credentials are not configured (PCGW_BOT_USERNAME, PCGW_BOT_PASSWORD).');
        }

        $token = $this->send(['action' => 'query', 'meta' => 'tokens', 'type' => 'login'])['query']['tokens']['logintoken'] ?? null;

        if (blank($token)) {
            throw new PcgwException('PCGamingWiki did not return a login token.');
        }

        // Sin reintentos: el token de login es de un solo uso.
        $result = $this->send([
            'action' => 'login',
            'lgname' => $username,
            'lgpassword' => $password,
            'lgtoken' => $token,
        ], post: true)['login'] ?? [];

        if (($result['result'] ?? null) !== 'Success') {
            throw new PcgwException('PCGamingWiki login failed: '.($result['reason'] ?? $result['result'] ?? 'unknown reason'));
        }

        $this->loggedIn = true;
    }

    /**
     * @param  array<string, string|int>  $params
     * @return array<string, mixed>
     */
    private function send(array $params, bool $post = false): array
    {
        Sleep::for(config('pcgw.pause_ms'))->milliseconds();

        $request = Http::withUserAgent(config('pcgw.user_agent'))
            ->withOptions(['cookies' => $this->cookies])
            ->connectTimeout(config('pcgw.connect_timeout'))
            ->timeout(config('pcgw.timeout'));

        $params['format'] = 'json';

        try {
            $response = $post
                ? $request->asForm()->post(config('pcgw.api_url'), $params)
                : $request
                    ->retry(config('pcgw.retries'), $this->retryDelay(...), $this->shouldRetry(...))
                    ->get(config('pcgw.api_url'), $params);

            $data = $response->throw()->json();
        } catch (RequestException $exception) {
            $status = $exception->response->status();
            $hint = $status === 403 ? ' (blocked by Cloudflare; the server IP may be flagged)' : '';

            throw new PcgwException("PCGamingWiki responded with HTTP {$status}{$hint}.", previous: $exception);
        } catch (ConnectionException $exception) {
            throw new PcgwException('Could not connect to PCGamingWiki: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($data)) {
            throw new PcgwException('PCGamingWiki returned a response that is not JSON.');
        }

        return $data;
    }

    private function retryDelay(int $attempt, Throwable $exception): int
    {
        if ($exception instanceof RequestException && $exception->response->tooManyRequests()) {
            return config('pcgw.rate_limited_delay_ms');
        }

        return $attempt * 2000;
    }

    private function shouldRetry(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException
                && ($exception->response->serverError() || $exception->response->tooManyRequests()));
    }
}
