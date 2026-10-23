{{-- Filtros por tecnología y búsqueda por nombre; el componente Livewire debe usar WithTechnologyFilters. --}}
@props(['activeFilters', 'search', 'placeholder'])

<section class="flex flex-col gap-5 border-y border-line py-5 lg:flex-row lg:items-end lg:justify-between">
    <fieldset class="flex flex-col gap-2">
        <legend class="label mb-2">{{ __('library.filters.label') }}</legend>
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach (\App\Enums\Technology::cases() as $technology)
                @php $isActive = in_array($technology->value, $activeFilters, true); @endphp
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
                    {{-- Casilla: refuerza el estado sin depender del color. --}}
                    <span @class(['size-2 border', 'border-accent bg-accent' => $isActive, 'border-current' => ! $isActive]) aria-hidden="true"></span>
                    {{ __('technologies.label.'.$technology->value) }}
                </button>
            @endforeach

            @if ($activeFilters !== [] || $search !== '')
                <button type="button" wire:click="clearFilters" class="focus-ring px-2 py-1 font-mono text-xs text-accent underline underline-offset-4">
                    {{ __('library.filters.clear') }}
                </button>
            @endif
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
</section>
