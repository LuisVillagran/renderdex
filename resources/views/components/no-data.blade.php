{{--
    "Sin datos en la wiki", con un enlace para completarlo: a la edición de su página o,
    si la wiki no la tiene, a su buscador.
--}}
@props(['game', 'name'])

@php
    $completeUrl = $game?->wikiEditUrl() ?? 'https://www.pcgamingwiki.com/w/index.php?search='.rawurlencode($name);
@endphp

<span {{ $attributes->class(['inline-flex flex-wrap items-center gap-x-2 font-mono text-[0.7rem] text-muted']) }}>
    <span class="rounded-sm border border-dashed border-line-strong px-1.5 leading-[1.125rem]">{{ $game ? __('library.no_data') : __('library.no_wiki_page') }}</span>
    <a href="{{ $completeUrl }}" rel="noopener" aria-label="{{ __('library.complete_label', ['name' => $name]) }}" class="focus-ring hover:text-accent">{{ __('library.complete') }} ↗</a>
</span>
