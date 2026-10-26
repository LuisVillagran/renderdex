{{--
    Portada vertical (2:3), como la caja del juego. Prueba en orden: la portada de la biblioteca
    de Steam, la de PCGamingWiki y el header de Steam; si todas fallan (p. ej. juegos nuevos cuyas
    imágenes Steam sirve en rutas con hash), queda el placeholder con las iniciales, que está detrás.
    wire:ignore evita que Livewire vuelva a la primera imagen en cada actualización.
--}}
@props(['appid' => null, 'cover' => null, 'title' => '', 'size' => 'sm', 'alt' => ''])

@php
    use App\Services\Pcgw\PcgwCover;
    use Illuminate\Support\Str;

    $steam = fn (string $file) => "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$appid}/{$file}";
    $sources = array_values(array_filter([
        $appid ? $steam('library_600x900.jpg') : null,
        $cover ? PcgwCover::thumbnail($cover, $size === 'lg' ? 480 : 120) : null,
        $appid ? $steam('header.jpg') : null,
    ]));

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
        'relative isolate block aspect-[2/3] shrink-0 overflow-hidden bg-raised ring-1 ring-line',
        match ($size) {
            'xs' => 'w-8',
            'md' => 'w-14',
            'lg' => 'w-40 sm:w-56',
            default => 'w-10 sm:w-12',
        },
    ]) }}
>
    <span
        aria-hidden="true"
        @class([
            'absolute inset-0 flex items-center justify-center font-display font-semibold tracking-tight text-muted',
            match ($size) {
                'xs' => 'text-[0.6rem]',
                'md' => 'text-base',
                'lg' => 'text-5xl',
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
            class="absolute inset-0 size-full object-cover"
            onerror="const next = JSON.parse(this.dataset.fallbacks); if (next.length) { this.dataset.fallbacks = JSON.stringify(next.slice(1)); this.src = next[0]; } else { this.remove(); }"
        >
    @endif
</span>
