{{--
    Barra lateral de filtros, como al explorar una tienda; el componente Livewire debe usar
    WithTechnologyFilters. Arriba, "Mi GPU"; después lo que la mayoría busca (reescalado,
    generación de frames, RT) y, plegadas, las tecnologías exactas. En móvil se pliega entera
    detrás de un botón "Filtros"; en escritorio siempre está abierta.
--}}
@props(['filter', 'hasActiveFilters', 'gpu', 'gpuModel' => null])

@php
    use App\Enums\Capability;
    use App\Enums\Technology;

    $activeCapabilities = array_map(fn (Capability $capability) => $capability->value, $filter->capabilities);
    $activeTechnologies = array_map(fn (Technology $technology) => $technology->value, $filter->technologies);
    $activeCount = count($activeCapabilities) + count($activeTechnologies) + ($filter->onlyWithData ? 1 : 0);
@endphp

{{-- El estado de los pliegues vive en Alpine para que no se cierren en cada actualización. --}}
<aside
    {{ $attributes->class(['flex flex-col gap-3 lg:sticky lg:top-24 lg:self-start']) }}
    aria-label="{{ __('library.filters.title') }}"
    x-data="{ open: false, more: @js($activeTechnologies !== []) }"
>
    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open ? 'true' : 'false'" aria-controls="filters-panel" class="focus-ring flex items-center justify-between rounded border border-line-strong bg-panel px-3 py-2 text-sm font-semibold lg:hidden">
        <span>
            {{ __('library.filters.title') }}
            @if ($activeCount > 0)
                <span class="ml-1 rounded-sm bg-accent px-1.5 text-xs text-on-accent">{{ $activeCount }}</span>
            @endif
        </span>
        <span aria-hidden="true" class="text-accent" x-text="open ? '−' : '+'">+</span>
    </button>

    <div id="filters-panel" x-show="open" x-cloak class="frame flex flex-col gap-5 p-4 lg:block! lg:space-y-5">
        <x-gpu-selector :gpu="$gpu" :gpu-model="$gpuModel" />

        <div class="border-t border-line pt-4">
        <fieldset class="flex flex-col gap-1">
            <legend class="label mb-2">
                {{ $filter->gpu ? __('library.filters.capabilities_on_gpu', ['gpu' => $filter->gpu->label()]) : __('library.filters.capabilities') }}
            </legend>
            <div class="flex flex-col gap-1.5">
                @foreach (Capability::cases() as $capability)
                    @php $isActive = in_array($capability->value, $activeCapabilities, true); @endphp
                    <button
                        type="button"
                        wire:key="capability-{{ $capability->value }}"
                        wire:click="toggleCapability('{{ $capability->value }}')"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        @class([
                            'focus-ring flex items-start gap-2.5 rounded px-2.5 py-2 text-left transition',
                            'bg-accent/12' => $isActive,
                            'hover:bg-raised' => ! $isActive,
                        ])
                    >
                        {{-- Casilla: refuerza el estado sin depender del color. --}}
                        <span @class(['mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-sm border text-[0.6rem] leading-none', 'border-accent bg-accent text-on-accent' => $isActive, 'border-line-strong' => ! $isActive]) aria-hidden="true">{{ $isActive ? '✓' : '' }}</span>
                        <span class="flex flex-col gap-0.5">
                            <span @class(['text-sm font-semibold', 'text-accent' => $isActive])>{{ __('library.capability.'.$capability->value.'.label') }}</span>
                            <span class="text-xs text-muted">{{ __('library.capability.'.$capability->value.'.hint') }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </fieldset>
        </div>

        <div class="flex flex-col gap-3 border-t border-line pt-4 text-sm">
            <label class="inline-flex cursor-pointer items-center gap-2 text-muted hover:text-fg">
                <input type="checkbox" wire:model.live="onlyWithData" class="size-4 rounded-sm accent-(--rd-accent)">
                {{ __('library.filters.only_with_data') }}
            </label>

            <button type="button" x-on:click="more = ! more" x-bind:aria-expanded="more ? 'true' : 'false'" aria-controls="more-filters" class="focus-ring inline-flex items-center gap-1.5 self-start text-muted hover:text-fg">
                <span aria-hidden="true" class="text-accent" x-text="more ? '−' : '+'">+</span>
                {{ __('library.filters.more') }}
                @if ($activeTechnologies !== [])
                    <span class="text-accent">({{ count($activeTechnologies) }})</span>
                @endif
            </button>

            <fieldset id="more-filters" x-show="more" x-cloak class="flex flex-col gap-2">
                <legend class="sr-only">{{ __('library.filters.label') }}</legend>
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach (Technology::cases() as $technology)
                        @php $isActive = in_array($technology->value, $activeTechnologies, true); @endphp
                        <button
                            type="button"
                            wire:key="filter-{{ $technology->value }}"
                            wire:click="toggleFilter('{{ $technology->value }}')"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                            @class([
                                'focus-ring inline-flex items-center gap-1.5 rounded border px-2 py-1 font-mono text-xs transition',
                                'border-accent bg-accent/12 text-accent' => $isActive,
                                'border-line-strong text-muted hover:border-fg hover:text-fg' => ! $isActive,
                            ])
                        >
                            {{ __('technologies.label.'.$technology->value) }}
                        </button>
                    @endforeach
                </div>
                <p class="text-xs text-muted">{{ __('library.filters.more_help') }}</p>
            </fieldset>

            @if ($hasActiveFilters)
                <button type="button" wire:click="clearFilters" class="focus-ring self-start text-accent underline underline-offset-4">
                    {{ __('library.filters.clear') }}
                </button>
            @endif
        </div>
    </div>
</aside>
