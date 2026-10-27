{{-- Selector de idioma: enlaces normales con ?lang=, que SetLocale recuerda en la sesión. --}}
<nav aria-label="{{ __('layout.locale.label') }}" class="flex items-center gap-0.5 text-xs font-semibold">
    @foreach (config('app.supported_locales') as $locale)
        @php $isCurrent = app()->getLocale() === $locale; @endphp
        <a
            href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}"
            hreflang="{{ $locale }}"
            lang="{{ $locale }}"
            title="{{ __('layout.locale.'.$locale) }}"
            @if ($isCurrent) aria-current="true" @endif
            @class([
                'focus-ring rounded px-1.5 py-1 underline-offset-[6px]',
                'text-fg underline decoration-accent decoration-2' => $isCurrent,
                'text-muted hover:text-fg' => ! $isCurrent,
            ])
        >
            {{ strtoupper($locale) }}
        </a>
    @endforeach
</nav>
