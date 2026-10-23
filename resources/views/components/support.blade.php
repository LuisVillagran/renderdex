@props(['value'])

@php
    // Símbolo + texto para lectores de pantalla: el color nunca es la única señal.
    [$symbol, $color] = match ($value) {
        \App\Enums\Support::Yes => ['✓', 'text-emerald-700 dark:text-emerald-400'],
        \App\Enums\Support::No => ['✗', 'text-zinc-500 dark:text-zinc-400'],
        default => ['?', 'text-amber-700 dark:text-amber-400'],
    };
    $label = __('technologies.support.'.$value->value);
@endphp

<span {{ $attributes->class(['inline-flex size-6 items-center justify-center font-semibold', $color]) }} title="{{ $label }}">
    <span aria-hidden="true">{{ $symbol }}</span>
    <span class="sr-only">{{ $label }}</span>
</span>
