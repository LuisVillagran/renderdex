{{-- Resalta cada palabra de $query dentro de $text; todo se escapa antes de armar el HTML. --}}
@props(['text', 'query' => ''])

@php
    $words = preg_split('/\s+/u', trim($query), flags: PREG_SPLIT_NO_EMPTY) ?: [];
    $parts = $words === []
        ? [$text]
        : (preg_split('/('.implode('|', array_map(fn (string $word) => preg_quote($word, '/'), $words)).')/iu', $text, flags: PREG_SPLIT_DELIM_CAPTURE) ?: [$text]);
    $html = collect($parts)
        ->map(fn (string $part, int $index) => $index % 2 === 1 ? '<mark class="bg-accent/20 text-fg">'.e($part).'</mark>' : e($part))
        ->implode('');
@endphp

{!! $html !!}
