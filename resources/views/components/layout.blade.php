@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
    <meta name="description" content="{{ __('layout.description') }}">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative isolate flex min-h-screen flex-col bg-canvas font-sans text-fg antialiased">
    <div class="bg-marks pointer-events-none fixed inset-0 -z-10" aria-hidden="true"></div>

    <header class="sticky top-0 z-20 border-b border-line bg-canvas/85 backdrop-blur-sm">
        <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4">
            <a href="{{ route('home') }}" class="focus-ring" aria-label="{{ __('layout.header.home') }}">
                <x-logo />
            </a>

            @if ($steamUser = session('steam_user'))
                <div class="flex items-center gap-1 sm:gap-3">
                    <a href="{{ route('library.show', $steamUser['steam_id']) }}" class="focus-ring flex items-center gap-2 px-2 py-1 hover:bg-raised">
                        @if ($steamUser['avatar'])
                            <img src="{{ $steamUser['avatar'] }}" alt="{{ __('layout.header.avatar_alt', ['name' => $steamUser['name']]) }}" width="28" height="28" class="size-7 ring-1 ring-line-strong">
                        @endif
                        <span class="max-w-32 truncate text-sm font-medium sm:max-w-none">{{ $steamUser['name'] ?? __('layout.header.your_account') }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="focus-ring px-2 py-1 font-mono text-xs text-muted uppercase hover:bg-raised hover:text-fg">
                            {{ __('auth.logout') }}
                        </button>
                    </form>
                </div>
            @else
                <p class="label hidden sm:block" aria-hidden="true">{{ __('layout.header.tagline') }}</p>
            @endif
        </div>
        <div class="ruler h-1.5" aria-hidden="true"></div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-10 sm:py-14">
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-canvas text-sm">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-8 md:flex-row md:items-end md:justify-between">
            <div class="flex flex-col gap-3">
                <x-logo :cursor="false" />
                <p class="max-w-md text-muted">
                    {!! __('layout.footer.data_credit', [
                        'pcgw' => '<a href="https://www.pcgamingwiki.com/" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener">PCGamingWiki</a>',
                        'license' => '<a href="https://creativecommons.org/licenses/by-nc-sa/3.0/" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener license">CC BY-NC-SA 3.0</a>',
                    ]) !!}
                </p>
                <p class="text-muted">{{ __('layout.footer.not_affiliated') }}</p>
            </div>

            <nav aria-label="{{ __('layout.footer.nav') }}" class="flex flex-wrap gap-x-5 gap-y-2 font-mono text-xs uppercase">
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
                <p class="hidden sm:block sm:ml-auto" aria-hidden="true">utf-8 · {{ app()->getLocale() }}</p>
            </div>
        </div>
    </footer>
</body>
</html>
