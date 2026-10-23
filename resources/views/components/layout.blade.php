@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
    <meta name="description" content="{{ __('layout.description') }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="rounded text-lg font-semibold tracking-tight focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none" aria-label="{{ __('layout.header.home') }}">
                Renderdex
            </a>

            @if ($steamUser = session('steam_user'))
                <div class="flex items-center gap-3">
                    <a href="{{ route('library.show', $steamUser['steam_id']) }}" class="flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-zinc-100 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none dark:hover:bg-zinc-800">
                        @if ($steamUser['avatar'])
                            <img src="{{ $steamUser['avatar'] }}" alt="{{ __('layout.header.avatar_alt', ['name' => $steamUser['name']]) }}" width="32" height="32" class="size-8 rounded">
                        @endif
                        <span class="text-sm font-medium">{{ $steamUser['name'] ?? __('layout.header.your_account') }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-2 py-1 text-sm text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                            {{ __('auth.logout') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-zinc-200 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6">
            <p>
                {!! __('layout.footer.data_credit', [
                    'pcgw' => '<a href="https://www.pcgamingwiki.com/" class="underline hover:text-zinc-900 dark:hover:text-zinc-100" rel="noopener">PCGamingWiki</a>',
                    'license' => '<a href="https://creativecommons.org/licenses/by-nc-sa/3.0/" class="underline hover:text-zinc-900 dark:hover:text-zinc-100" rel="noopener license">CC BY-NC-SA 3.0</a>',
                ]) !!}
                @if ($lastSyncAt)
                    {{ __('layout.footer.last_sync', ['date' => $lastSyncAt->isoFormat('LL')]) }}
                @else
                    {{ __('layout.footer.never_synced') }}
                @endif
            </p>
            <p class="flex flex-wrap gap-x-4 gap-y-1">
                <a href="{{ config('app.repository_url') }}" class="underline hover:text-zinc-900 dark:hover:text-zinc-100" rel="noopener">{{ __('layout.footer.repository') }}</a>
                <span>{{ __('layout.footer.not_affiliated') }}</span>
            </p>
        </div>
    </footer>
</body>
</html>
