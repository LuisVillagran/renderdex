{{--
    Filtros y búsqueda por nombre; el componente Livewire debe usar WithTechnologyFilters.
    Arriba, lo que la mayoría busca (reescalado, generación de frames, RT); las tecnologías
    exactas quedan plegadas en "Más filtros".
--}}
@props(['filter', 'hasActiveFilters', 'search', 'placeholder'])

@php
    use App\Enums\Capability;
    use App\Enums\Technology;

    $activeCapabilities = array_map(fn (Capability $capability) => $capability->value, $filter->capabilities);
    $activeTechnologies = array_map(fn (Technology $technology) => $technology->value, $filter->technologies);
@endphp

<section class="flex flex-col gap-4 border-y border-line py-5">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <fieldset class="flex flex-col gap-2">
            <legend class="label mb-2">
                {{ $filter->gpu ? __('library.filters.capabilities_on_gpu', ['gpu' => $filter->gpu->label()]) : __('library.filters.capabilities') }}
            </legend>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach (Capability::cases() as $capability)
                    @php $isActive = in_array($capability->value, $activeCapabilities, true); @endphp
                    <button
                        type="button"
                        wire:key="capability-{{ $capability->value }}"
                        wire:click="toggleCapability('{{ $capability->value }}')"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        @class([
                            'focus-ring flex items-start gap-2.5 border px-3 py-2.5 text-left transition active:translate-y-px',
                            'border-accent bg-accent/12' => $isActive,
                            'border-line-strong hover:border-fg' => ! $isActive,
                        ])
                    >
                        {{-- Casilla: refuerza el estado sin depender del color. --}}
                        <span @class(['mt-1 size-2.5 shrink-0 border', 'border-accent bg-accent' => $isActive, 'border-muted' => ! $isActive]) aria-hidden="true"></span>
                        <span class="flex flex-col gap-0.5">
                            <span @class(['text-sm font-semibold', 'text-accent' => $isActive])>{{ __('library.capability.'.$capability->value.'.label') }}</span>
                            <span class="font-mono text-[0.7rem] text-muted">{{ __('library.capability.'.$capability->value.'.hint') }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </fieldset>

        <div
            class="flex flex-col gap-2 lg:w-80"
            x-on:keydown.window="if ($event.key === '/' && ! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { $event.preventDefault(); $refs.search.focus() }"
        >
            <label for="library-search" class="label">{{ __('library.search.label') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 font-mono text-sm text-accent" aria-hidden="true">›</span>
                <input
                    id="library-search"
                    type="search"
                    x-ref="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ $placeholder }}"
                    aria-keyshortcuts="/"
                    autocomplete="off"
                    class="w-full border border-line-strong bg-canvas py-2 pr-10 pl-7 font-mono text-sm placeholder:text-muted focus:border-accent focus:outline-2 focus:-outline-offset-1 focus:outline-accent"
                >
                <kbd class="pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 border border-line-strong px-1.5 font-mono text-[0.65rem] text-muted" aria-hidden="true">/</kbd>
            </div>
        </div>
    </div>

    {{-- El estado de "Más filtros" vive en Alpine para que no se cierre en cada actualización. --}}
    <div class="flex flex-col gap-3" x-data="{ more: @js($activeTechnologies !== []) }">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 font-mono text-xs">
            <label class="inline-flex cursor-pointer items-center gap-2 text-muted hover:text-fg">
                <input type="checkbox" wire:model.live="onlyWithData" class="size-3.5 accent-(--rd-accent)">
                {{ __('library.filters.only_with_data') }}
            </label>

            <button type="button" x-on:click="more = ! more" x-bind:aria-expanded="more ? 'true' : 'false'" aria-controls="more-filters" class="focus-ring inline-flex items-center gap-1.5 text-muted uppercase hover:text-fg">
                <span aria-hidden="true" class="text-accent" x-text="more ? '−' : '+'">+</span>
                {{ __('library.filters.more') }}
                @if ($activeTechnologies !== [])
                    <span class="text-accent">({{ count($activeTechnologies) }})</span>
                @endif
            </button>

            @if ($hasActiveFilters)
                <button type="button" wire:click="clearFilters" class="focus-ring text-accent underline underline-offset-4">
                    {{ __('library.filters.clear') }}
                </button>
            @endif
        </div>

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
                            'focus-ring inline-flex items-center gap-1.5 border px-2.5 py-1 font-mono text-xs transition active:translate-y-px',
                            'border-accent bg-accent/12 text-accent' => $isActive,
                            'border-line-strong text-muted hover:border-fg hover:text-fg' => ! $isActive,
                        ])
                    >
                        <span @class(['size-2 border', 'border-accent bg-accent' => $isActive, 'border-current' => ! $isActive]) aria-hidden="true"></span>
                        {{ __('technologies.label.'.$technology->value) }}
                    </button>
                @endforeach
            </div>
            <p class="text-xs text-muted">{{ __('library.filters.more_help') }}</p>
        </fieldset>
    </div>
</section>
