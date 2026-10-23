{{-- Marca propia: una "R" dentro de un cuadro, con el degradado del sitio. --}}
@php
    // Id único: el logo aparece más de una vez por página.
    $gradientId = 'renderdex-logo-'.\Illuminate\Support\Str::random(6);
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    <svg viewBox="0 0 32 32" fill="none" class="size-7 shrink-0" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="2" y1="2" x2="30" y2="30" gradientUnits="userSpaceOnUse">
                <stop style="stop-color: var(--rd-accent-2)" />
                <stop offset="1" style="stop-color: var(--rd-accent)" />
            </linearGradient>
        </defs>
        <rect x="2.25" y="2.25" width="27.5" height="27.5" rx="8" stroke="url(#{{ $gradientId }})" stroke-width="2.5" />
        <path d="M11 23V9.5h6a4.25 4.25 0 0 1 0 8.5h-6m6.25 0L22 23" stroke="url(#{{ $gradientId }})" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span class="text-lg font-bold tracking-tight">Render<span class="text-gradient">dex</span></span>
</span>
