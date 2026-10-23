{{-- Marca propia: un píxel chico proyectado a un cuadro grande, como un diagrama de reescalado. --}}
@props(['cursor' => true])

<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 32 32" fill="none" class="size-7 shrink-0" aria-hidden="true">
        <rect x="3.5" y="3.5" width="25" height="25" stroke="currentColor" stroke-width="1.5" />
        <path d="M14 18 28.5 3.5" stroke="currentColor" stroke-width="1" stroke-dasharray="2 2" />
        <rect x="6" y="16" width="10" height="10" class="fill-accent" />
    </svg>
    <span class="font-mono text-[0.95rem] font-bold tracking-tight lowercase">
        renderdex<span @class(['ml-0.5 inline-block h-[1.05em] w-[0.5em] translate-y-[0.15em] bg-accent', 'animate-blink' => $cursor]) aria-hidden="true"></span>
    </span>
</span>
