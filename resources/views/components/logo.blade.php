{{--
    Marca propia: una R cuya pierna se deshace en píxeles que se apagan, como un borde todavía
    sin reescalar. El violeta de la marca es fijo en los dos temas (igual que public/favicon.svg).
--}}
@props(['tagline' => false])

<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 32 32" class="size-7 shrink-0" aria-hidden="true">
        <rect width="32" height="32" rx="7" fill="#7c4dff" />
        <path d="M9 25V7.5h7a5 5 0 0 1 0 10H9" stroke="#fff" stroke-width="3.2" fill="none" stroke-linejoin="round" />
        <rect x="15" y="17" width="4" height="4" fill="#fff" />
        <rect x="19" y="21" width="4" height="4" fill="#fff" opacity=".8" />
        <rect x="23" y="25" width="3" height="3" fill="#fff" opacity=".55" />
    </svg>
    <span @class(['flex-col leading-none', 'hidden sm:flex' => $tagline, 'flex' => ! $tagline])>
        <span class="font-display text-lg font-extrabold tracking-tight">Renderdex</span>
        @if ($tagline)
            <span class="mt-0.5 hidden text-[0.7rem] text-muted xl:block">{{ __('layout.tagline') }}</span>
        @endif
    </span>
</span>
