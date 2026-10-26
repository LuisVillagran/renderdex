{{--
    Celdas de tecnologías de una fila, en pantallas grandes (en móvil, ver x-technology-compact).
    Un juego sin datos ocupa una sola celda, en vez de una fila de "?".
--}}
@props(['game', 'rowKey', 'name', 'gpuProfile', 'highlightedVendor'])

@php
    $groups = \App\Enums\Technology::byVendor();
    $hasData = (bool) $game?->has_data;
@endphp

@if ($gpuProfile)
    <td wire:key="gpu-{{ $rowKey }}" class="hidden bg-accent/5 px-3 py-3 lg:table-cell">
        @if ($hasData)
            <x-gpu-summary :game="$game" :gpu="$gpuProfile" />
        @else
            <x-no-data :game="$game" :name="$name" />
        @endif
    </td>
@endif

@if ($hasData)
    @foreach ($groups as $vendor => $groupTechnologies)
        <td
            wire:key="cell-{{ $rowKey }}-{{ $vendor }}"
            @if ($gpuProfile) x-show="all" x-cloak @endif
            @class([
                'hidden px-3 py-3 lg:table-cell',
                'bg-accent/5' => ! $gpuProfile && $highlightedVendor?->value === $vendor,
            ])
        >
            <x-tech-pills :game="$game" :technologies="$groupTechnologies" />
        </td>
    @endforeach
@else
    <td wire:key="cell-{{ $rowKey }}-none" colspan="{{ count($groups) }}" @if ($gpuProfile) x-show="all" x-cloak @endif class="hidden px-3 py-3 lg:table-cell">
        @unless ($gpuProfile)
            <x-no-data :game="$game" :name="$name" />
        @endunless
    </td>
@endif
