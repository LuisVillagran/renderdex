{{-- Selector de tema; la lógica vive en el script del <head> del layout (window.renderdexTheme). --}}
@php
    // Íconos de trazo propio: monitor (según el sistema), sol y luna.
    $icons = [
        'system' => '<rect x="2.5" y="3.5" width="15" height="10" rx="1.5" /><path d="M7 16.5h6M10 13.5v3" />',
        'light' => '<circle cx="10" cy="10" r="3.25" /><path d="M10 2.5v1.75M10 15.75v1.75M2.5 10h1.75M15.75 10h1.75M4.7 4.7l1.24 1.24M14.06 14.06l1.24 1.24M4.7 15.3l1.24-1.24M14.06 5.94l1.24-1.24" />',
        'dark' => '<path d="M16 12.2A6.5 6.5 0 0 1 7.8 4a6.5 6.5 0 1 0 8.2 8.2Z" />',
    ];
@endphp

<div
    role="group"
    aria-label="{{ __('layout.theme.label') }}"
    x-data="{ theme: window.renderdexTheme?.get() ?? 'system' }"
    class="flex overflow-hidden rounded border border-line"
>
    @foreach ($icons as $option => $icon)
        <button
            type="button"
            x-on:click="window.renderdexTheme.set('{{ $option }}'); theme = '{{ $option }}'"
            x-bind:aria-pressed="theme === '{{ $option }}'"
            x-bind:class="theme === '{{ $option }}' ? 'segment-on' : 'text-muted hover:bg-raised hover:text-fg'"
            title="{{ __('layout.theme.'.$option) }}"
            @class(['focus-ring flex items-center gap-1.5 px-1.5 py-1.5 text-xs font-semibold sm:px-2', 'border-l border-line' => ! $loop->first])
        >
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">{!! $icon !!}</svg>
            <span class="sr-only">{{ __('layout.theme.'.$option) }}</span>
        </button>
    @endforeach
</div>
