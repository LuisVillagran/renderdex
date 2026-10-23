@props(['technology', 'support', 'version' => null])

@php
    use App\Enums\Support;

    // Cada estado tiene una señal además del color: relleno (sí), tachado (no) o borde punteado (sin datos).
    $state = __('technologies.support.'.$support->value);
    $description = __('technologies.description.'.$technology->value);
    $suffix = $support === Support::Yes && $version ? ' '.$version : '';
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded-md px-1.5 py-0.5 font-mono text-[0.7rem] leading-4 font-medium whitespace-nowrap',
    'bg-accent/10 text-accent ring-1 ring-accent/40 ring-inset glow' => $support === Support::Yes,
    'text-muted line-through decoration-muted/70' => $support === Support::No,
    'text-muted outline-1 -outline-offset-1 outline-line outline-dashed' => $support === Support::Unknown,
]) }} title="{{ $description }}: {{ $state }}">
    <span aria-hidden="true">{{ __('technologies.column.'.$technology->value).$suffix }}</span>
    <span class="sr-only">{{ $description.$suffix }}:</span>
    <span class="sr-only">{{ $state }}</span>
</span>
