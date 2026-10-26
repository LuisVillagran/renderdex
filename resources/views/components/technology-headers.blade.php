{{--
    Encabezados de las columnas de tecnologías. Con una generación de GPU elegida hay una sola
    columna "En tu GPU" y las de cada marca aparecen solo con "Ver todas" (estado Alpine `all`).
--}}
@props(['gpuProfile', 'highlightedVendor'])

@php $groups = \App\Enums\Technology::byVendor(); @endphp

@if ($gpuProfile)
    <th scope="col" class="label hidden bg-accent/8 px-3 py-3 font-normal text-accent! shadow-[inset_0_2px_0_var(--rd-accent)] lg:table-cell">
        {{ __('library.gpu.column', ['gpu' => $gpuProfile->label()]) }}
    </th>
@endif

@foreach ($groups as $vendor => $groupTechnologies)
    <th
        scope="col"
        wire:key="th-{{ $vendor }}"
        @if ($gpuProfile) x-show="all" x-cloak @endif
        @class([
            'label hidden px-3 py-3 font-normal lg:table-cell',
            'bg-accent/8 text-accent! shadow-[inset_0_2px_0_var(--rd-accent)]' => ! $gpuProfile && $highlightedVendor?->value === $vendor,
        ])
    >
        {{ __('technologies.group.'.$vendor) }}
    </th>
@endforeach
