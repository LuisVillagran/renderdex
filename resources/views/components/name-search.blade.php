{{-- Búsqueda por nombre dentro de los resultados; el componente Livewire debe usar WithTechnologyFilters. --}}
@props(['placeholder'])

<div
    {{ $attributes->class(['flex flex-col gap-2']) }}
    x-on:keydown.window="if ($event.key === '/' && ! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { $event.preventDefault(); $refs.search.focus() }"
>
    <label for="library-search" class="sr-only">{{ __('library.search.label') }}</label>
    <div class="relative">
        <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.38 9.84l3.14 3.14a.75.75 0 1 0 1.06-1.06l-3.14-3.14A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" /></svg>
        <input
            id="library-search"
            type="search"
            x-ref="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ $placeholder }}"
            aria-keyshortcuts="/"
            autocomplete="off"
            class="w-full rounded border border-line-strong bg-panel py-2.5 pr-10 pl-9 text-sm placeholder:text-muted focus:border-accent focus:outline-2 focus:-outline-offset-1 focus:outline-accent"
        >
        <kbd class="pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 rounded-sm border border-line-strong px-1.5 font-mono text-[0.65rem] text-muted" aria-hidden="true">/</kbd>
    </div>
</div>
