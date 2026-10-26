{{-- Marca propia: un píxel chico proyectado a un cuadro grande, como un diagrama de reescalado. --}}
@props(['tagline' => false])

<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 32 32" fill="none" class="size-7 shrink-0" aria-hidden="true">
        <rect x="3.5" y="3.5" width="25" height="25" rx="3" stroke="currentColor" stroke-width="1.5" />
        <path d="M14 18 28.5 3.5" stroke="currentColor" stroke-width="1" stroke-dasharray="2 2" />
        <rect x="6" y="16" width="10" height="10" rx="1.5" class="fill-accent" />
    </svg>
    <span class="flex flex-col leading-none">
        <span class="font-display text-lg font-semibold tracking-tight">Renderdex</span>
        @if ($tagline)
            <span class="mt-0.5 hidden text-[0.7rem] text-muted xl:block">{{ __('layout.tagline') }}</span>
        @endif
    </span>
</span>
