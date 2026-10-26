{{-- Selector de idioma: enlaces normales con ?lang=, que SetLocale recuerda en la sesión. --}}
<nav aria-label="{{ __('layout.locale.label') }}" class="flex border border-line-strong">
    @foreach (config('app.supported_locales') as $locale)
        @php $isCurrent = app()->getLocale() === $locale; @endphp
        <a
            href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}"
            hreflang="{{ $locale }}"
            lang="{{ $locale }}"
            title="{{ __('layout.locale.'.$locale) }}"
            @if ($isCurrent) aria-current="true" @endif
            @class([
                'focus-ring px-2 py-1 font-mono text-[0.7rem] font-bold uppercase',
                'border-l border-line-strong' => ! $loop->first,
                'bg-accent text-on-accent' => $isCurrent,
                'text-muted hover:bg-raised hover:text-fg' => ! $isCurrent,
            ])
        >
            {{ $locale }}
        </a>
    @endforeach
</nav>
