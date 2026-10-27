{{--
    Celda "En tu GPU": lo mejor que puedes activar de cada capacidad (en ámbar) y, debajo,
    lo que el juego trae pero tu GPU no puede usar o depende de la versión.
--}}
@props(['game', 'gpu'])

@inject('gpus', \App\Services\GpuCapabilities::class)

@php
    $summary = $gpus->summary($game, $gpu);
    $notes = [
        ...array_map(fn ($technology) => __('technologies.label.'.$technology->value).': '.__('gpu.requires.'.$technology->value), $summary['blocked']),
        ...array_map(fn ($technology) => __('technologies.label.'.$technology->value).': '.__('gpu.depends.'.$technology->value), $summary['depends']),
    ];
@endphp

<div {{ $attributes->class(['flex flex-col gap-1']) }}>
    @if ($summary['usable'] !== [])
        <ul class="flex flex-wrap gap-1" aria-label="{{ __('library.gpu.column', ['gpu' => $gpu->label()]) }}">
            @foreach ($summary['usable'] as $item)
                <li class="inline-flex items-center rounded-sm border border-transparent bg-chip px-1.5 font-narrow text-xs leading-[1.125rem] font-semibold text-fg whitespace-nowrap">
                    {{ trim(__('technologies.label.'.$item['technology']->value).' '.$item['version']) }}
                </li>
            @endforeach
        </ul>
    @elseif ($game?->has_data)
        <span class="font-narrow text-[0.7rem] text-muted">{{ __('library.gpu.nothing_usable') }}</span>
    @endif

    @foreach ($notes as $note)
        <span class="font-narrow text-[0.65rem] leading-tight text-muted">{{ $note }}</span>
    @endforeach
</div>
