{{-- Etiqueta mono de sección. Antes se "decodificaba" al cargar; ahora es fija para no competir con las portadas. --}}
@props(['text'])

<span {{ $attributes }}>{{ $text }}</span>
