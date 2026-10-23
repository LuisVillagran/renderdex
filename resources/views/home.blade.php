@php
    use App\Enums\Technology;
    use Illuminate\Support\Number;
@endphp

<x-layout>
    <div class="flex flex-col gap-20 sm:gap-24">
        <section class="grid items-center gap-10 lg:grid-cols-[1.1fr_1fr] lg:gap-14">
            <div class="flex flex-col gap-6">
                @if ($gameCount > 0)
                    <p class="inline-flex items-center gap-2 self-start rounded-full border border-line bg-panel/70 px-3 py-1 font-mono text-xs text-muted backdrop-blur">
                        <span class="glow size-1.5 rounded-full bg-accent" aria-hidden="true"></span>
                        {{ __('home.eyebrow', ['count' => Number::format($gameCount)]) }}
                    </p>
                @endif

                <h1 class="text-4xl leading-[1.1] font-bold tracking-tight text-balance sm:text-5xl">
                    {!! __('home.title', ['technologies' => '<span class="text-gradient">'.e(__('home.title_technologies')).'</span>']) !!}
                </h1>
                <p class="max-w-xl text-lg text-muted">{{ __('home.intro') }}</p>

                @if (session('error'))
                    <p class="rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-800 dark:text-red-200" role="alert">
                        {{ session('error') }}
                    </p>
                @endif

                <div class="panel flex flex-col gap-5 p-5 sm:p-6">
                    <div class="flex flex-col gap-3">
                        @if ($steamUser = session('steam_user'))
                            <a href="{{ route('library.show', $steamUser['steam_id']) }}" class="focus-ring bg-brand self-start rounded-lg px-5 py-2.5 font-semibold shadow-lg shadow-accent-2/20 transition hover:brightness-110">
                                {{ __('home.steam_login.open_library') }}
                            </a>
                        @else
                            {{-- Botón oficial "Sign in through Steam": la única excepción a la regla de no usar logos de terceros. --}}
                            <a href="{{ route('auth.steam') }}" class="focus-ring self-start rounded transition hover:brightness-110">
                                <img src="https://community.akamai.steamstatic.com/public/images/signinthroughsteam/sits_01.png" alt="{{ __('home.steam_login.alt') }}" width="180" height="35">
                            </a>
                            <p class="text-xs text-muted">{{ __('home.steam_login.note') }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 font-mono text-xs text-muted uppercase" aria-hidden="true">
                        <span class="h-px flex-1 bg-line"></span>
                        {{ __('home.or') }}
                        <span class="h-px flex-1 bg-line"></span>
                    </div>

                    <livewire:library-lookup />
                </div>
            </div>

            @if ($showcase->isNotEmpty())
                <figure class="flex flex-col gap-3">
                    <div class="panel overflow-hidden shadow-2xl shadow-accent-2/10">
                        <div class="flex items-center justify-between border-b border-line px-4 py-3 font-mono text-xs text-muted">
                            <span class="tracking-widest uppercase">{{ __('home.preview.label') }}</span>
                            <span class="flex gap-1.5" aria-hidden="true">
                                <span class="size-2 rounded-full bg-line"></span>
                                <span class="size-2 rounded-full bg-line"></span>
                                <span class="size-2 rounded-full bg-accent"></span>
                            </span>
                        </div>
                        <ul class="divide-y divide-line/70">
                            @foreach ($showcase as $appid)
                                <li class="flex flex-col gap-3 p-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{{ $appid->steam_appid }}/capsule_184x69.jpg"
                                            alt=""
                                            width="92"
                                            height="35"
                                            loading="lazy"
                                            onerror="this.onerror = null; this.src = '{{ asset('images/cover-placeholder.svg') }}'"
                                            class="h-[35px] w-[92px] max-w-none rounded-md bg-raised object-cover ring-1 ring-line"
                                        >
                                        <span class="font-semibold">{{ $appid->game->title }}</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                        @foreach (Technology::byVendor() as $vendor => $technologies)
                                            <x-tech-pills :game="$appid->game" :technologies="$technologies" />
                                        @endforeach
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <figcaption class="text-xs text-muted">{{ __('home.preview.caption') }}</figcaption>
                </figure>
            @endif
        </section>

        <section class="flex flex-col gap-6" aria-labelledby="how-title">
            <h2 id="how-title" class="font-mono text-xs tracking-widest text-accent uppercase">{{ __('home.how.title') }}</h2>
            <ol class="grid gap-4 sm:grid-cols-3">
                @foreach (__('home.how.steps') as $step)
                    <li class="panel flex flex-col gap-2 p-5">
                        <span class="text-gradient font-mono text-sm font-bold" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h3 class="text-lg font-semibold">{{ $step['title'] }}</h3>
                        <p class="text-sm text-muted">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="grid gap-4 sm:grid-cols-2">
            <div class="panel flex flex-col gap-2 p-5">
                <h2 class="font-semibold">{{ __('home.privacy_help.title') }}</h2>
                <p class="text-sm text-muted">{{ __('home.privacy_help.steps') }}</p>
            </div>
            <div class="panel flex flex-col gap-2 p-5">
                <h2 class="font-semibold">{{ __('home.privacy_help.family_sharing_title') }}</h2>
                <p class="text-sm text-muted">{{ __('home.privacy_help.family_sharing') }}</p>
            </div>
        </section>
    </div>
</x-layout>
