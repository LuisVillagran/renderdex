@inject('summary', \App\Services\SupportSummary::class)

@php
    use Illuminate\Support\Number;
@endphp

<x-layout>
    <div class="flex flex-col gap-20 sm:gap-28">
        <section class="grid items-start gap-12 lg:grid-cols-[1.05fr_1fr] lg:gap-14">
            <div class="flex flex-col gap-7">
                @if ($gameCount > 0)
                    <p class="text-sm font-medium text-muted">
                        <x-decode :text="__('home.eyebrow', ['count' => Number::format($gameCount)])" />
                    </p>
                @endif

                <h1 class="font-display text-4xl leading-[1.02] font-extrabold tracking-[-0.025em] text-balance sm:text-5xl lg:text-[3.25rem]">
                    {!! __('home.title', ['technologies' => e(__('home.title_technologies'))]) !!}
                </h1>
                <p class="max-w-xl text-lg text-muted">{{ __('home.intro') }}</p>

                @if (session('error'))
                    <p class="border border-danger/60 bg-danger/10 px-4 py-3 text-sm text-danger" role="alert">
                        {{ session('error') }}
                    </p>
                @endif

                {{-- Lo más directo: buscar un juego, sin cuenta ni perfil. --}}
                <div class="flex flex-col gap-2">
                    <livewire:game-search-box input-id="home-search" :label="__('home.catalog.title')" />
                    <p class="text-sm text-muted">{{ __('home.catalog.body') }}</p>
                </div>

                <div class="frame">
                    <p class="label flex items-center justify-between border-b border-line px-4 py-2">
                        <span>{{ __('home.access.label') }}</span>
                        
                    </p>

                    <div class="flex flex-col gap-4 p-4 sm:p-5">
                        <div class="flex flex-col gap-3">
                            @if ($steamUser = session('steam_user'))
                                <a href="{{ route('library.show', $steamUser['steam_id']) }}" class="focus-ring rounded self-start bg-accent px-5 py-2.5 text-sm font-semibold text-on-accent hover:brightness-110">
                                    {{ __('home.steam_login.open_library') }} →
                                </a>
                            @else
                                {{-- Botón oficial "Sign in through Steam": la única excepción a la regla de no usar logos de terceros. --}}
                                <a href="{{ route('auth.steam') }}" class="focus-ring self-start transition hover:brightness-110">
                                    <img src="https://community.akamai.steamstatic.com/public/images/signinthroughsteam/sits_01.png" alt="{{ __('home.steam_login.alt') }}" width="180" height="35">
                                </a>
                                <p class="text-xs text-muted">{{ __('home.steam_login.note') }}</p>
                            @endif
                        </div>

                        {{-- Alternativa para quien no quiere iniciar sesión: plegada para no competir con el botón. --}}
                        <details class="group border-t border-line pt-4">
                            <summary class="focus-ring flex cursor-pointer list-none items-center gap-2 text-sm text-muted hover:text-fg">
                                <span class="text-accent group-open:hidden" aria-hidden="true">+</span>
                                <span class="hidden text-accent group-open:inline" aria-hidden="true">−</span>
                                {{ __('home.access.paste_profile') }}
                            </summary>
                            <div class="pt-4">
                                <livewire:library-lookup />
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            @if ($showcase->isNotEmpty())
                {{-- Mosaico de portadas reales: cada una muestra lo que soporta según la wiki. --}}
                <figure class="flex flex-col gap-3 lg:mt-6">
                    <ul class="grid grid-cols-3 gap-2.5 sm:grid-cols-4">
                        @foreach ($showcase as $appid)
                            @php $labels = $summary->labels($appid->game); @endphp
                            <li class="animate-rise" style="--delay: {{ 150 + $loop->index * 70 }}ms">
                                <a href="{{ $appid->game->url() }}" class="group focus-ring relative block overflow-hidden rounded-md ring-1 ring-line transition duration-200 hover:ring-fg/60" aria-label="{{ $appid->game->title }}">
                                    <x-game-cover :appid="$appid->steam_appid" :game="$appid->game" :title="$appid->game->title" size="card" class="ring-0!" />
                                    @if ($labels !== [])
                                        <span class="absolute inset-x-0 bottom-0 flex gap-1 overflow-hidden border-t border-line bg-canvas/90 px-1.5 py-1" aria-hidden="true">
                                            @foreach (array_slice($labels, 0, 2) as $label)
                                                <span class="chip text-[0.7rem] leading-4">{{ $label }}</span>
                                            @endforeach
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <figcaption class="text-sm text-muted">
                        {{ __('home.preview.caption') }}
                    </figcaption>
                </figure>
            @endif
        </section>

        <section class="flex flex-col gap-8" aria-labelledby="how-title">
            <h2 id="how-title" class="font-display text-2xl font-semibold tracking-tight"><x-decode :text="__('home.how.title')" /></h2>
            <ol class="grid gap-10 sm:grid-cols-3 sm:gap-6">
                @foreach (__('home.how.steps') as $step)
                    <li class="flex flex-col gap-3" data-reveal style="--delay: {{ $loop->index * 140 }}ms">
                        {{-- Nodo del pipeline: número y conector hacia el paso siguiente. --}}
                        <div class="flex items-center gap-3" aria-hidden="true">
                            <span class="border border-accent px-1.5 font-narrow text-xs text-accent">{{ sprintf('%02d', $loop->iteration) }}</span>
                            @unless ($loop->last)
                                <span class="hidden h-px flex-1 border-t border-dashed border-line-strong sm:block" data-connector></span>
                                <span class="hidden font-narrow text-xs text-line-strong sm:block">▶</span>
                            @endunless
                        </div>
                        <h3 class="font-display text-xl font-semibold">{{ $step['title'] }}</h3>
                        <p class="max-w-xs text-sm text-muted">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section aria-labelledby="faq-title" class="flex flex-col gap-8">
            <h2 id="faq-title" class="font-display text-2xl font-semibold tracking-tight"><x-decode :text="__('home.faq_title')" /></h2>
            <dl class="grid border-y border-line sm:grid-cols-2">
                @foreach ([
                    [__('home.privacy_help.title'), __('home.privacy_help.steps')],
                    [__('home.privacy_help.family_sharing_title'), __('home.privacy_help.family_sharing')],
                ] as [$question, $answer])
                    <div data-reveal style="--delay: {{ $loop->index * 140 }}ms" @class(['flex flex-col gap-2 py-6 sm:px-6', 'border-b border-line sm:border-r sm:border-b-0 sm:pl-0' => $loop->first, 'sm:pr-0' => $loop->last])>
                        <dt class="flex gap-3 font-display text-lg font-semibold"><span class="font-narrow text-sm leading-7 text-accent" aria-hidden="true">Q.</span>{{ $question }}</dt>
                        <dd class="pl-8 text-sm text-muted">{{ $answer }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>
</x-layout>
