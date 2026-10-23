{{-- Texto que se "decodifica" al cargar (resources/js/app.js). Los lectores de pantalla leen la copia fija. --}}
@props(['text'])

<span data-decode aria-hidden="true" {{ $attributes }}>{{ $text }}</span><span class="sr-only">{{ $text }}</span>
