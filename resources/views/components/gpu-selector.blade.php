{{-- Selector "Mi GPU". La elección se recuerda en localStorage y se restaura al cargar, sin pisar filtros de un link compartido. --}}
@props(['gpu', 'hasFilters'])

<fieldset
    class="flex flex-col gap-2"
    x-data="{
        init() {
            let saved = null;
            try { saved = localStorage.getItem('renderdex.gpu') } catch (e) {}
            if (saved && saved !== $wire.gpu) {
                $wire.selectGpu(saved, {{ $hasFilters ? 'false' : 'true' }});
            }
        },
        chooseGpu(gpu) {
            try { localStorage.setItem('renderdex.gpu', gpu) } catch (e) {}
            $wire.selectGpu(gpu);
        },
    }"
>
    <legend class="label mb-2">{{ __('library.gpu.label') }}</legend>
    <div class="grid grid-cols-4 border border-line-strong bg-panel">
        @foreach (['nvidia', 'amd', 'intel', 'all'] as $option)
            <button
                type="button"
                wire:key="gpu-{{ $option }}"
                x-on:click="chooseGpu('{{ $option }}')"
                aria-pressed="{{ $gpu === $option ? 'true' : 'false' }}"
                @class([
                    'focus-ring px-3 py-2 font-mono text-xs font-bold uppercase transition active:translate-y-px sm:px-5',
                    'border-l border-line-strong' => ! $loop->first,
                    'bg-accent text-on-accent' => $gpu === $option,
                    'text-muted hover:bg-raised hover:text-fg' => $gpu !== $option,
                ])
            >
                {{ __('technologies.vendor.'.$option) }}
            </button>
        @endforeach
    </div>
    <p class="text-xs text-muted">{{ __('library.gpu.help') }}</p>
</fieldset>
