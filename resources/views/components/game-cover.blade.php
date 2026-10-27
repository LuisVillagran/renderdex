{{--
    Portada vertical (2:3), como la caja del juego. Prueba en orden:
    1. El arte de la tienda de Steam que guardó covers:sync (los juegos nuevos lo tienen en rutas
       con hash) o, si todavía no se consultó, la URL fija library_600x900.jpg.
    2. La portada de SteamGridDB.
    3. La de PCGamingWiki (Cloudflare la bloquea desde algunas redes).
    4. El header de Steam.
    Si todas fallan, queda el placeholder con las iniciales sobre el damero de "textura faltante".
    Con :game se toman del juego las portadas guardadas.
    wire:ignore evita que Livewire vuelva a la primera imagen en cada actualización.
--}}
@props([
    'appid' => null,
    'game' => null,
    'cover' => null,
    'steamArtwork' => null,
    'gridCover' => null,
    'gridThumb' => null,
    'title' => '',
    'size' => 'sm',
    'alt' => '',
])

@php
    use App\Services\Pcgw\PcgwCover;
    use Illuminate\Support\Str;

    $cover ??= $game?->cover_url;
    $steamArtwork ??= $game?->steam_artwork;
    $gridCover ??= $game?->grid_cover_url;
    $gridThumb ??= $game?->grid_cover_thumb_url;

    $steam = fn (string $file) => "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$appid}/{$file}";
    $steamCover = in_array($size, ['card', 'lg'], true)
        ? ($steamArtwork['cover_2x'] ?? $steamArtwork['cover'] ?? null)
        : ($steamArtwork['cover'] ?? null);
    // Sin arte guardado se prueba la URL fija; si Steam ya respondió sin portada, daría 404.
    $guessSteamCover = $appid && $steamArtwork === null;

    $sources = array_values(array_unique(array_filter([
        $steamCover,
        $guessSteamCover ? $steam('library_600x900.jpg') : null,
        $size === 'lg' ? ($gridCover ?? $gridThumb) : ($gridThumb ?? $gridCover),
        $cover ? PcgwCover::thumbnail($cover, match ($size) { 'lg' => 480, 'card' => 300, default => 120 }) : null,
        $steamArtwork['header'] ?? ($appid ? $steam('header.jpg') : null),
    ])));

    $initials = Str::of($title)
        ->replaceMatches('/[^\pL\pN\s]+/u', ' ')
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<span
    wire:ignore
    {{ $attributes->class([
        'missing-texture relative isolate block aspect-[2/3] shrink-0 overflow-hidden rounded-sm ring-1 ring-line',
        match ($size) {
            'xs' => 'w-8',
            'md' => 'w-14',
            'lg' => 'w-40 sm:w-56',
            'card' => 'w-full',
            default => 'w-10 sm:w-12',
        },
    ]) }}
>
    <span
        aria-hidden="true"
        @class([
            'absolute inset-0 flex items-center justify-center font-display font-extrabold tracking-tight text-fg/80',
            match ($size) {
                'xs' => 'text-[0.6rem]',
                'md' => 'text-base',
                'lg', 'card' => 'text-5xl',
                default => 'text-xs',
            },
        ])
    >{{ $initials ?: '?' }}</span>

    @if ($sources !== [])
        <img
            src="{{ $sources[0] }}"
            data-fallbacks="{{ json_encode(array_slice($sources, 1), JSON_UNESCAPED_SLASHES) }}"
            alt="{{ $alt }}"
            loading="lazy"
            decoding="async"
            class="absolute inset-0 size-full object-cover transition duration-500 ease-out group-hover:scale-[1.04]"
            onerror="const next = JSON.parse(this.dataset.fallbacks); if (next.length) { this.dataset.fallbacks = JSON.stringify(next.slice(1)); this.src = next[0]; } else { this.remove(); }"
        >
    @endif
</span>
