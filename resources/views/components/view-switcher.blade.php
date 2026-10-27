{{-- Cuadrícula o tabla. Se recuerda en localStorage y se restaura al cargar; el componente usa WithTechnologyFilters. --}}
@props(['layout'])

<div
    role="group"
    aria-label="{{ __('library.layout.label') }}"
    class="flex overflow-hidden rounded border border-line"
    x-data="{
        init() {
            let saved = null;
            try { saved = localStorage.getItem('renderdex.layout') } catch (e) {}
            if (saved && saved !== $wire.layout) {
                $wire.setLayout(saved);
            }
        },
        choose(layout) {
            try { localStorage.setItem('renderdex.layout', layout) } catch (e) {}
            $wire.setLayout(layout);
        },
    }"
>
    @foreach ([
        'grid' => '<rect x="3" y="3" width="5.5" height="5.5" rx="1" /><rect x="11.5" y="3" width="5.5" height="5.5" rx="1" /><rect x="3" y="11.5" width="5.5" height="5.5" rx="1" /><rect x="11.5" y="11.5" width="5.5" height="5.5" rx="1" />',
        'table' => '<path d="M3 5h14M3 10h14M3 15h14" />',
    ] as $option => $icon)
        <button
            type="button"
            wire:key="layout-{{ $option }}"
            x-on:click="choose('{{ $option }}')"
            aria-pressed="{{ $layout === $option ? 'true' : 'false' }}"
            @class([
                'focus-ring flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold',
                'border-l border-line' => ! $loop->first,
                'segment-on' => $layout === $option,
                'text-muted hover:bg-raised hover:text-fg' => $layout !== $option,
            ])
        >
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true">{!! $icon !!}</svg>
            {{ __('library.layout.'.$option) }}
        </button>
    @endforeach
</div>
