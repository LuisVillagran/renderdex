@php
    use App\Enums\Technology;
    use Illuminate\Support\Number;
@endphp

<x-layout>
    <div class="flex flex-col gap-20 sm:gap-28">
        <section class="grid items-start gap-12 lg:grid-cols-[1.05fr_1fr] lg:gap-14">
            <div class="flex flex-col gap-7">
                @if ($gameCount > 0)
                    <p class="font-mono text-xs text-muted">
                        <span class="text-accent" aria-hidden="true">//</span>
                        <x-decode :text="__('home.eyebrow', ['count' => Number::format($gameCount)])" />
                    </p>
                @endif

                <h1 class="font-display text-4xl leading-[1.05] font-semibold tracking-tight text-balance sm:text-5xl lg:text-[3.5rem]">
                    {!! __('home.title', ['technologies' => '<span class="text-render">'.e(__('home.title_technologies')).'</span>']) !!}
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
                        <span aria-hidden="true">steam · openid</span>
                    </p>

                    <div class="flex flex-col gap-4 p-4 sm:p-5">
                        <div class="flex flex-col gap-3">
                            @if ($steamUser = session('steam_user'))
                                <a href="{{ route('library.show', $steamUser['steam_id']) }}" class="focus-ring self-start bg-accent px-5 py-2.5 font-mono text-sm font-bold text-on-accent uppercase hover:brightness-110">
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
                            <summary class="focus-ring flex cursor-pointer list-none items-center gap-2 font-mono text-xs text-muted uppercase hover:text-fg">
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
                <figure class="flex flex-col gap-3 lg:mt-14">
                    <div class="frame relative overflow-hidden">
                        <span class="scanline z-10" style="--delay: 1400ms" aria-hidden="true"></span>
                        <div class="flex items-center justify-between gap-4 border-b border-line px-4 py-2">
                            <p class="font-mono text-xs">
                                <span class="text-accent" aria-hidden="true">$</span>
                                <span class="sr-only">{{ __('home.preview.label') }}:</span>
                                <span class="animate-type" style="--chars: {{ mb_strlen(__('home.preview.command')) }}; --delay: 300ms" aria-hidden="true">{{ __('home.preview.command') }}</span><span class="animate-blink ml-0.5 inline-block h-3.5 w-1.5 translate-y-0.5 bg-fg" aria-hidden="true"></span>
                            </p>
                            <p class="label" aria-hidden="true">{{ trans_choice('home.preview.rows', $showcase->count()) }}</p>
                        </div>

                        <ul class="divide-y divide-line">
                            @foreach ($showcase as $appid)
                                @php $rowDelay = 1100 + $loop->index * 260; @endphp
                                <li class="animate-rise flex flex-col gap-3 px-4 py-4" style="--delay: {{ $rowDelay }}ms">
                                    <div class="flex items-center gap-3">
                                        <x-game-cover :appid="$appid->steam_appid" />
                                        <a href="{{ $appid->game->url() }}" class="focus-ring font-display text-lg leading-tight font-semibold decoration-accent underline-offset-4 hover:text-accent hover:underline">{{ $appid->game->title }}</a>
                                    </div>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-[auto_auto_auto_1fr]">
                                        @foreach (Technology::byVendor() as $vendor => $technologies)
                                            <div class="flex flex-col gap-1">
                                                <span class="label text-[0.625rem]" aria-hidden="true">{{ __('technologies.group.'.$vendor) }}</span>
                                                <x-tech-pills :game="$appid->game" :technologies="$technologies" :delay="$rowDelay + 150 + $loop->index * 90" class="flex-nowrap" />
                                            </div>
                                        @endforeach
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <figcaption class="font-mono text-xs text-muted">
                        <span class="text-accent" aria-hidden="true">↳</span> {{ __('home.preview.caption') }}
                    </figcaption>
                </figure>
            @endif
        </section>

        <section class="flex flex-col gap-8" aria-labelledby="how-title">
            <h2 id="how-title" class="font-mono text-xs text-muted uppercase"><span class="text-accent" aria-hidden="true">//</span> <x-decode :text="__('home.how.title')" /></h2>
            <ol class="grid gap-10 sm:grid-cols-3 sm:gap-6">
                @foreach (__('home.how.steps') as $step)
                    <li class="flex flex-col gap-3" data-reveal style="--delay: {{ $loop->index * 140 }}ms">
                        {{-- Nodo del pipeline: número y conector hacia el paso siguiente. --}}
                        <div class="flex items-center gap-3" aria-hidden="true">
                            <span class="border border-accent px-1.5 font-mono text-xs text-accent">{{ sprintf('%02d', $loop->iteration) }}</span>
                            @unless ($loop->last)
                                <span class="hidden h-px flex-1 border-t border-dashed border-line-strong sm:block" data-connector></span>
                                <span class="hidden font-mono text-xs text-line-strong sm:block">▶</span>
                            @endunless
                        </div>
                        <h3 class="font-display text-xl font-semibold">{{ $step['title'] }}</h3>
                        <p class="max-w-xs text-sm text-muted">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section aria-labelledby="faq-title" class="flex flex-col gap-8">
            <h2 id="faq-title" class="font-mono text-xs text-muted uppercase"><span class="text-accent" aria-hidden="true">//</span> <x-decode :text="__('home.faq_title')" /></h2>
            <dl class="grid border-y border-line sm:grid-cols-2">
                @foreach ([
                    [__('home.privacy_help.title'), __('home.privacy_help.steps')],
                    [__('home.privacy_help.family_sharing_title'), __('home.privacy_help.family_sharing')],
                ] as [$question, $answer])
                    <div data-reveal style="--delay: {{ $loop->index * 140 }}ms" @class(['flex flex-col gap-2 py-6 sm:px-6', 'border-b border-line sm:border-r sm:border-b-0 sm:pl-0' => $loop->first, 'sm:pr-0' => $loop->last])>
                        <dt class="flex gap-3 font-display text-lg font-semibold"><span class="font-mono text-sm leading-7 text-accent" aria-hidden="true">Q.</span>{{ $question }}</dt>
                        <dd class="pl-8 text-sm text-muted">{{ $answer }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>
</x-layout>
