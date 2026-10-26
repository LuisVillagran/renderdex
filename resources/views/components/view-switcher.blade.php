{{-- Cuadrícula o tabla. Se recuerda en localStorage y se restaura al cargar; el componente usa WithTechnologyFilters. --}}
@props(['layout'])

<div
    role="group"
    aria-label="{{ __('library.layout.label') }}"
    class="flex border border-line-strong"
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
    @foreach (['grid' => '▦', 'table' => '☰'] as $option => $icon)
        <button
            type="button"
            wire:key="layout-{{ $option }}"
            x-on:click="choose('{{ $option }}')"
            aria-pressed="{{ $layout === $option ? 'true' : 'false' }}"
            @class([
                'focus-ring flex items-center gap-1.5 px-2.5 py-1 font-mono text-[0.7rem] font-bold uppercase',
                'border-l border-line-strong' => ! $loop->first,
                'bg-accent text-on-accent' => $layout === $option,
                'text-muted hover:bg-raised hover:text-fg' => $layout !== $option,
            ])
        >
            <span aria-hidden="true">{{ $icon }}</span>
            {{ __('library.layout.'.$option) }}
        </button>
    @endforeach
</div>
