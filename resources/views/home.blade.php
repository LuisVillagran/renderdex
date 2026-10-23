<x-layout>
    <div class="mx-auto flex max-w-2xl flex-col gap-8">
        <div class="flex flex-col gap-3">
            <h1 class="text-3xl font-semibold tracking-tight text-balance">{{ __('home.title') }}</h1>
            <p class="text-zinc-600 dark:text-zinc-400">{{ __('home.intro') }}</p>
        </div>

        @if (session('error'))
            <p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ session('error') }}
            </p>
        @endif

        <section class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            @if ($steamUser = session('steam_user'))
                <a href="{{ route('library.show', $steamUser['steam_id']) }}" class="self-start rounded-lg bg-sky-600 px-4 py-2 font-medium text-white hover:bg-sky-700 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:outline-none dark:focus-visible:ring-offset-zinc-900">
                    {{ __('home.steam_login.open_library') }}
                </a>
            @else
                {{-- Botón oficial "Sign in through Steam": la única excepción a la regla de no usar logos de terceros. --}}
                <a href="{{ route('auth.steam') }}" class="self-start rounded focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:outline-none dark:focus-visible:ring-offset-zinc-900">
                    <img src="https://community.akamai.steamstatic.com/public/images/signinthroughsteam/sits_01.png" alt="{{ __('home.steam_login.alt') }}" width="180" height="35">
                </a>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('home.steam_login.note') }}</p>
            @endif
        </section>

        <div class="flex items-center gap-3 text-sm text-zinc-500" aria-hidden="true">
            <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></span>
            {{ __('home.or') }}
            <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></span>
        </div>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <livewire:library-lookup />
        </section>

        <section class="flex flex-col gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <h2 class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('home.privacy_help.title') }}</h2>
            <p>{{ __('home.privacy_help.steps') }}</p>
            <p>{{ __('home.privacy_help.family_sharing') }}</p>
        </section>
    </div>
</x-layout>
