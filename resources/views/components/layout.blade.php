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
    {{-- Fondo decorativo: cuadrícula y un brillo arriba. --}}
    <div class="pointer-events-none fixed inset-0 -z-10" aria-hidden="true">
        <div class="bg-grid absolute inset-0"></div>
        <div class="absolute -top-48 left-1/2 h-96 w-[56rem] max-w-full -translate-x-1/2 rounded-full bg-accent-2/20 blur-3xl"></div>
        <div class="absolute -top-32 left-[60%] h-72 w-[32rem] max-w-full -translate-x-1/2 rounded-full bg-accent/15 blur-3xl"></div>
    </div>

    <header class="sticky top-0 z-20 border-b border-line/70 bg-canvas/70 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="focus-ring rounded-lg" aria-label="{{ __('layout.header.home') }}">
                <x-logo />
            </a>

            @if ($steamUser = session('steam_user'))
                <div class="flex items-center gap-1 sm:gap-2">
                    <a href="{{ route('library.show', $steamUser['steam_id']) }}" class="focus-ring flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-raised">
                        @if ($steamUser['avatar'])
                            <img src="{{ $steamUser['avatar'] }}" alt="{{ __('layout.header.avatar_alt', ['name' => $steamUser['name']]) }}" width="32" height="32" class="size-8 rounded-md ring-1 ring-line">
                        @endif
                        <span class="max-w-32 truncate text-sm font-medium sm:max-w-none">{{ $steamUser['name'] ?? __('layout.header.your_account') }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="focus-ring rounded-lg px-2 py-1 text-sm text-muted hover:bg-raised hover:text-fg">
                            {{ __('auth.logout') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:py-12">
        {{ $slot }}
    </main>

    <footer class="border-t border-line/70 text-sm text-muted">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-8 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col gap-2">
                <x-logo class="text-fg" />
                <p class="max-w-md">
                    {!! __('layout.footer.data_credit', [
                        'pcgw' => '<a href="https://www.pcgamingwiki.com/" class="underline decoration-line underline-offset-2 hover:text-fg" rel="noopener">PCGamingWiki</a>',
                        'license' => '<a href="https://creativecommons.org/licenses/by-nc-sa/3.0/" class="underline decoration-line underline-offset-2 hover:text-fg" rel="noopener license">CC BY-NC-SA 3.0</a>',
                    ]) !!}
                </p>
            </div>
            <div class="flex flex-col gap-2 font-mono text-xs sm:items-end sm:text-right">
                <p class="flex items-center gap-2">
                    <span class="size-1.5 rounded-full {{ $lastSyncAt ? 'bg-accent glow' : 'bg-muted' }}" aria-hidden="true"></span>
                    @if ($lastSyncAt)
                        {{ __('layout.footer.last_sync', ['date' => $lastSyncAt->isoFormat('LL')]) }}
                    @else
                        {{ __('layout.footer.never_synced') }}
                    @endif
                </p>
                <a href="{{ config('app.repository_url') }}" class="focus-ring rounded underline decoration-line underline-offset-2 hover:text-fg" rel="noopener">{{ __('layout.footer.repository') }}</a>
                <p>{{ __('layout.footer.not_affiliated') }}</p>
            </div>
        </div>
    </footer>
</body>
</html>
