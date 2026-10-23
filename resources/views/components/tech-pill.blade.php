@props(['technology', 'support', 'version' => null])

@php
    use App\Enums\Support;

    // Cada estado tiene una señal además del color: relleno (sí), tachado (no) o borde punteado (sin datos).
    $state = __('technologies.support.'.$support->value);
    $description = __('technologies.description.'.$technology->value);
    $suffix = $support === Support::Yes && $version ? ' '.$version : '';
@endphp

<span {{ $attributes->class([
    'inline-flex items-center border px-1.5 font-mono text-[0.7rem] leading-[1.125rem] font-medium whitespace-nowrap',
    'border-accent/60 bg-accent/12 text-accent' => $support === Support::Yes,
    'border-transparent text-muted line-through decoration-muted/70' => $support === Support::No,
    'border-dashed border-line-strong text-muted' => $support === Support::Unknown,
]) }} title="{{ $description }}: {{ $state }}">
    <span aria-hidden="true">{{ __('technologies.column.'.$technology->value).$suffix }}</span>
    <span class="sr-only">{{ $description.$suffix }}:</span>
    <span class="sr-only">{{ $state }}</span>
</span>
