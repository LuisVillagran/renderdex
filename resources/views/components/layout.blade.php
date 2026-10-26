@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'noindex' => false])

@php
    $fullTitle = $title ? $title.' · '.config('app.name') : config('app.name').' · '.__('layout.tagline');
    $description ??= __('layout.description');
    $canonical ??= url()->current();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    @if ($noindex)
        <meta name="robots" content="noindex">
    @endif
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#eef1f5" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#111419" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'es_ES' }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
    @else
        <meta property="og:image" content="{{ asset('images/og.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ __('layout.og_image_alt') }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">

    {{-- Va en línea y antes de los estilos: aplica el tema antes de pintar, sin parpadeo. --}}
    <script>
        (() => {
            document.documentElement.classList.add('js');

            const key = 'renderdex.theme';
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)');
            const stored = () => {
                try { return localStorage.getItem(key) } catch (e) { return null }
            };
            const apply = () => {
                const choice = stored();
                const theme = choice === 'light' || choice === 'dark' ? choice : (systemDark.matches ? 'dark' : 'light');
                document.documentElement.dataset.theme = theme;
                document.querySelectorAll('meta[name="theme-color"]').forEach((meta) => {
                    meta.content = theme === 'dark' ? '#111419' : '#eef1f5';
                });
            };

            apply();
            systemDark.addEventListener('change', apply);

            window.renderdexTheme = {
                get: () => stored() ?? 'system',
                set(choice) {
                    try {
                        choice === 'system' ? localStorage.removeItem(key) : localStorage.setItem(key, choice);
                    } catch (e) {}
                    apply();
                },
            };
        })();
    </script>

    {{ $head ?? '' }}

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative isolate flex min-h-screen flex-col bg-canvas font-sans text-fg antialiased">
    <a href="#main" class="sr-only z-50 bg-accent px-4 py-2 font-mono text-sm font-bold text-on-accent focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        {{ __('layout.skip_to_content') }}
    </a>


    <header class="sticky top-0 z-20 border-b border-line bg-panel">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4">
            <a href="{{ route('home') }}" class="focus-ring shrink-0" aria-label="{{ __('layout.header.home') }}">
                <x-logo tagline />
            </a>

            {{-- En /juegos ya hay un buscador en la página: no se repite. --}}
            @unless (request()->routeIs('catalog'))
                <div class="hidden max-w-sm flex-1 md:block">
                    <livewire:game-search-box compact input-id="header-search" />
                </div>
            @endunless

            <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                <nav aria-label="{{ __('layout.header.nav') }}" class="flex items-center">
                    @foreach (['catalog' => __('layout.header.catalog'), 'changes.index' => __('layout.header.changes')] as $route => $label)
                        <a href="{{ route($route) }}" @class([
                            'focus-ring rounded px-3 py-1.5 text-sm font-medium transition hover:bg-raised hover:text-fg',
                            'bg-raised text-fg' => request()->routeIs($route),
                            'text-muted' => ! request()->routeIs($route),
                        ]) @if (request()->routeIs($route)) aria-current="page" @endif>
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>

                @if ($steamUser = session('steam_user'))
                    <div class="flex items-center gap-1 sm:gap-3">
                        <a href="{{ route('library.show', $steamUser['steam_id']) }}" class="focus-ring flex items-center gap-2 rounded px-2 py-1 hover:bg-raised">
                            @if ($steamUser['avatar'])
                                <img src="{{ $steamUser['avatar'] }}" alt="{{ __('layout.header.avatar_alt', ['name' => $steamUser['name']]) }}" width="28" height="28" class="size-7 rounded-sm ring-1 ring-line-strong">
                            @endif
                            <span class="max-w-32 truncate text-sm font-medium sm:max-w-none">{{ $steamUser['name'] ?? __('layout.header.your_account') }}</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="focus-ring rounded px-2 py-1 text-sm text-muted hover:bg-raised hover:text-fg">
                                {{ __('auth.logout') }}
                            </button>
                        </form>
                    </div>
                @endif

                <x-locale-switcher />
                <x-theme-switcher />
            </div>
        </div>
        <div class="ruler relative h-1.5" data-ruler aria-hidden="true">
            <span class="ruler-cursor"></span>
        </div>
    </header>

    <main id="main" tabindex="-1" class="mx-auto w-full max-w-6xl flex-1 px-4 py-10 outline-none sm:py-14">
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-canvas text-sm">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-8 md:flex-row md:items-end md:justify-between">
            <div class="flex flex-col gap-3">
                <x-logo />
                <p class="text-sm text-muted">{{ __('layout.tagline') }}</p>
                <p class="max-w-md text-muted">
                    {!! __('layout.footer.data_credit', [
                        'pcgw' => '<a href="https://www.pcgamingwiki.com/" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener">PCGamingWiki</a>',
                        'license' => '<a href="https://creativecommons.org/licenses/by-nc-sa/3.0/" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener license">CC BY-NC-SA 3.0</a>',
                    ]) !!}
                </p>
                <p class="text-muted">{{ __('layout.footer.not_affiliated') }}</p>
            </div>

            <nav aria-label="{{ __('layout.footer.nav') }}" class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                <a href="{{ route('privacy') }}" class="focus-ring text-muted hover:text-accent">{{ __('layout.footer.privacy') }}</a>
                <a href="{{ config('app.repository_url') }}" class="focus-ring text-muted hover:text-accent" rel="noopener">{{ __('layout.footer.repository') }} ↗</a>
            </nav>
        </div>

        {{-- Barra de estado, como la de un editor. --}}
        <div class="border-t border-line">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-5 gap-y-1 px-4 py-2 font-mono text-[0.7rem] text-muted">
                <p class="flex items-center gap-2">
                    <span @class(['size-1.5', 'bg-accent' => $lastSyncAt, 'bg-line-strong' => ! $lastSyncAt]) aria-hidden="true"></span>
                    @if ($lastSyncAt)
                        {{ __('layout.footer.last_sync', ['date' => $lastSyncAt->isoFormat('LL')]) }}
                    @else
                        {{ __('layout.footer.never_synced') }}
                    @endif
                </p>
                <p class="hidden sm:block" aria-hidden="true">{{ __('layout.footer.status_source') }}</p>
                <p class="hidden sm:block sm:ml-auto" aria-hidden="true">
                    @if (defined('LARAVEL_START'))
                        {{-- Tiempo real que tardó el servidor en armar la página. --}}
                        render {{ Number::format((microtime(true) - LARAVEL_START) * 1000, precision: 0) }} ms ·
                    @endif
                    utf-8 · {{ app()->getLocale() }}
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
