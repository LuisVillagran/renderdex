{{-- Portada desde el CDN de Steam, con un respaldo si falla o si el juego no tiene AppID. --}}
@props(['appid' => null])

@php $placeholder = asset('images/cover-placeholder.svg'); @endphp

<img
    src="{{ $appid ? "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$appid}/capsule_184x69.jpg" : $placeholder }}"
    alt=""
    width="92"
    height="35"
    loading="lazy"
    @if ($appid) onerror="this.onerror = null; this.src = '{{ $placeholder }}'" @endif
    {{ $attributes->class(['h-[35px] w-[92px] max-w-none bg-raised object-cover ring-1 ring-line']) }}
>
