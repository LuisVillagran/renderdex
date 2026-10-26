{{--
    Tarjeta de la cuadrícula: la portada manda y las tecnologías van encima de su borde inferior.
    Con una generación de GPU elegida, muestra lo que se puede usar en ella; si no, lo que soporta.
    El slot `meta` va bajo el título (horas, enlaces a la wiki y la tienda, "Novedad").
--}}
@props(['game', 'appid' => null, 'name', 'gpuProfile' => null, 'query' => ''])

@inject('summary', \App\Services\SupportSummary::class)
@inject('gpus', \App\Services\GpuCapabilities::class)

@php
    $hasData = (bool) $game?->has_data;

    $chips = match (true) {
        ! $hasData => [],
        $gpuProfile !== null => array_map(
            fn (array $item) => trim(__('technologies.label.'.$item['technology']->value).' '.$item['version']),
            $gpus->summary($game, $gpuProfile)['usable'],
        ),
        default => $summary->labels($game),
    };

    // Dos píldoras entran en una línea sin tapar la portada; el resto se resume en "+N".
    $visible = array_slice($chips, 0, 2);
    $hidden = count($chips) - count($visible);
@endphp

<article {{ $attributes->class(['group flex flex-col gap-2.5']) }}>
    <a
        @if ($game) href="{{ $game->url() }}" @endif
        @class([
            'focus-ring relative block outline-offset-2 ring-1 ring-line transition',
            'hover:ring-accent' => $game,
        ])
        @if ($game) aria-label="{{ $name }}" @else aria-hidden="true" tabindex="-1" @endif
    >
        <x-game-cover :appid="$appid" :cover="$game?->cover_url" :title="$name" size="card" class="ring-0!" />

        {{-- Franja sólida, sin degradado: se lee igual sobre cualquier portada. --}}
        <span class="absolute inset-x-0 bottom-0 flex items-center gap-1 overflow-hidden border-t border-line bg-canvas/90 px-2 py-1.5">
            @forelse ($visible as $chip)
                <span class="border border-accent/60 bg-accent/12 px-1.5 font-mono text-[0.65rem] leading-[1.125rem] font-medium whitespace-nowrap text-accent">{{ $chip }}</span>
            @empty
                <span class="border border-dashed border-line-strong px-1.5 font-mono text-[0.65rem] leading-[1.125rem] whitespace-nowrap text-muted">
                    {{ match (true) {
                        ! $game => __('library.no_wiki_page'),
                        ! $hasData => __('library.no_data'),
                        $gpuProfile !== null => __('library.gpu.nothing_usable'),
                        default => __('search.none_supported'),
                    } }}
                </span>
            @endforelse
            @if ($hidden > 0)
                <span class="font-mono text-[0.65rem] text-muted" title="{{ implode(' · ', array_slice($chips, 2)) }}">+{{ $hidden }}</span>
            @endif
        </span>
    </a>

    <div class="flex min-w-0 flex-col gap-1">
        @if ($game)
            <a href="{{ $game->url() }}" tabindex="-1" class="line-clamp-2 text-sm leading-snug font-semibold text-balance decoration-accent underline-offset-4 group-hover:text-accent hover:underline"><x-highlight :text="$name" :query="$query" /></a>
        @else
            <span class="line-clamp-2 text-sm leading-snug font-semibold text-balance">{{ $name }}</span>
        @endif

        <span class="flex flex-wrap items-center gap-x-2.5 gap-y-1 font-mono text-[0.65rem] text-muted">
            {{ $meta ?? '' }}
            @unless ($hasData)
                <x-no-data :game="$game" :name="$name" class="[&>span:first-child]:hidden" />
            @endunless
        </span>
    </div>
</article>
