{{-- Selector de tema; la lógica vive en el script del <head> del layout (window.renderdexTheme). --}}
<div
    role="group"
    aria-label="{{ __('layout.theme.label') }}"
    x-data="{ theme: window.renderdexTheme?.get() ?? 'system' }"
    class="flex border border-line-strong"
>
    @foreach (['system' => '◐', 'light' => '☀', 'dark' => '☾'] as $option => $icon)
        <button
            type="button"
            x-on:click="window.renderdexTheme.set('{{ $option }}'); theme = '{{ $option }}'"
            x-bind:aria-pressed="theme === '{{ $option }}'"
            x-bind:class="theme === '{{ $option }}' ? 'bg-accent text-on-accent' : 'text-muted hover:bg-raised hover:text-fg'"
            title="{{ __('layout.theme.'.$option) }}"
            @class(['focus-ring flex items-center gap-1.5 px-2 py-1 font-mono text-[0.7rem] font-bold uppercase', 'border-l border-line-strong' => ! $loop->first])
        >
            <span aria-hidden="true">{{ $icon }}</span>
            <span class="sr-only sm:not-sr-only">{{ __('layout.theme.'.$option) }}</span>
        </button>
    @endforeach
</div>
