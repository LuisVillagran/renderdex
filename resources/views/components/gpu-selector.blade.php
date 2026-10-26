{{--
    Selector "Mi GPU": marca y, opcionalmente, generación. La elección se recuerda en localStorage
    y se restaura al cargar. No toca los filtros, así que un link compartido se ve igual.
--}}
@props(['gpu', 'gpuModel' => null])

@php
    use App\Enums\GpuProfile;
    use App\Enums\Vendor;

    $vendor = Vendor::tryFrom($gpu);
    $profiles = $vendor && $vendor !== Vendor::All ? GpuProfile::forVendor($vendor) : [];
@endphp

<fieldset
    class="flex flex-col gap-2"
    x-data="{
        init() {
            let saved = null;
            let savedModel = null;
            try {
                saved = localStorage.getItem('renderdex.gpu');
                savedModel = localStorage.getItem('renderdex.gpu-model');
            } catch (e) {}
            if ((saved && saved !== $wire.gpu) || (savedModel && savedModel !== $wire.gpuModel)) {
                $wire.restoreGpu(saved ?? $wire.gpu, savedModel ?? '');
            }
        },
        chooseGpu(gpu) {
            try {
                localStorage.setItem('renderdex.gpu', gpu);
                localStorage.removeItem('renderdex.gpu-model');
            } catch (e) {}
            $wire.selectGpu(gpu);
        },
        chooseModel(model) {
            try {
                model ? localStorage.setItem('renderdex.gpu-model', model) : localStorage.removeItem('renderdex.gpu-model');
            } catch (e) {}
            $wire.selectGpuModel(model);
        },
    }"
>
    <legend class="label mb-2">{{ __('library.gpu.label') }}</legend>
    <div class="flex flex-col gap-2">
        {{-- gap-px sobre el color del borde: separadores que funcionan en 4 columnas y en 2×2. --}}
        <div class="grid grid-cols-4 gap-px overflow-hidden rounded border border-line-strong bg-line-strong lg:grid-cols-2">
            @foreach (['nvidia', 'amd', 'intel', 'all'] as $option)
                <button
                    type="button"
                    wire:key="gpu-{{ $option }}"
                    x-on:click="chooseGpu('{{ $option }}')"
                    aria-pressed="{{ $gpu === $option ? 'true' : 'false' }}"
                    @class([
                        'focus-ring px-2 py-2 text-sm font-semibold transition active:translate-y-px',
                        'bg-accent text-on-accent' => $gpu === $option,
                        'bg-panel text-muted hover:bg-raised hover:text-fg' => $gpu !== $option,
                    ])
                >
                    {{ __('technologies.vendor.'.$option) }}
                </button>
            @endforeach
        </div>

        @if ($profiles !== [])
            <div class="flex">
                <label for="gpu-model" class="sr-only">{{ __('gpu.model_label') }}</label>
                <select
                    id="gpu-model"
                    wire:key="gpu-model-{{ $gpu }}"
                    x-on:change="chooseModel($event.target.value)"
                    @class([
                        'focus-ring w-full rounded border bg-panel px-3 py-2 text-sm',
                        'border-accent text-accent' => $gpuModel !== null,
                        'border-line-strong text-muted' => $gpuModel === null,
                    ])
                >
                    <option value="">{{ __('gpu.model_unspecified') }}</option>
                    @foreach ($profiles as $profile)
                        <option value="{{ $profile->value }}" @selected($gpuModel === $profile->value)>{{ $profile->label() }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
    <p class="text-xs text-muted">{{ $profiles !== [] && $gpuModel === null ? __('gpu.model_help') : __('library.gpu.help') }}</p>
</fieldset>
