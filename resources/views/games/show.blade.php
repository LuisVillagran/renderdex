@inject('classifier', \App\Services\FeatureClassifier::class)

@php
    use App\Enums\Support;
    use App\Enums\Technology;
    use App\Services\Pcgw\PcgwCover;
    use Illuminate\Support\Arr;

    $technologyList = Arr::join($supported, ', ', __('game.list_and'));
    $metaTitle = __('game.meta_title', ['title' => $game->title]);
    $metaDescription = $supported !== []
        ? __('game.meta_description', ['title' => $game->title, 'technologies' => $technologyList])
        : __('game.meta_description_none', ['title' => $game->title]);
    // Para compartir conviene una imagen horizontal: el header de Steam o, sin AppID, la portada de la wiki.
    $steamImage = fn (string $field, string $file) => $game->steam_artwork[$field]
        ?? ($appid !== null && $game->steam_artwork === null ? "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$appid}/{$file}" : null);
    $heroImage = $steamImage('hero', 'library_hero.jpg');
    $shareImage = match (true) {
        ($header = $steamImage('header', 'header.jpg')) !== null => $header,
        $game->cover_url !== null => PcgwCover::thumbnail($game->cover_url, 600),
        default => null,
    };
    $rawList = fn (?string $list) => $list ? collect(explode(',', $list))->map(fn (string $value) => trim($value))->implode(', ') : null;
@endphp

<x-layout :title="$metaTitle" :description="$metaDescription" :image="$shareImage" :canonical="$game->url()" :noindex="! $game->has_data">
    <article class="flex flex-col gap-10">
        <nav aria-label="{{ __('game.breadcrumb') }}" class="font-narrow text-xs text-muted">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ route('catalog') }}" class="focus-ring hover:text-accent">{{ __('layout.header.catalog') }}</a></li>
                <li aria-hidden="true" class="text-accent">›</li>
                <li><span aria-current="page" class="text-fg">{{ $game->title }}</span></li>
            </ol>
        </nav>

        <header class="relative isolate">
            @if ($heroImage)
                {{-- Banda con el arte panorámico de Steam; si no existe, se quita y queda solo el espacio. --}}
                <div class="absolute inset-x-0 top-0 -z-10 h-44 overflow-hidden border border-line bg-raised sm:h-64" aria-hidden="true">
                    <img
                        src="{{ $heroImage }}"
                        alt=""
                        class="size-full object-cover opacity-60"
                        onerror="this.parentElement.remove()"
                    >
                    <div class="absolute inset-0 bg-canvas/35"></div>
                </div>
            @endif

            <div @class(['grid items-end gap-6 sm:grid-cols-[auto_1fr] sm:gap-10', 'pt-20 sm:px-8 sm:pt-32' => $heroImage])>
            <div class="frame w-fit self-start bg-canvas p-1.5 shadow-[0_0_0_6px_var(--rd-canvas)]" data-reveal>
                <x-game-cover :appid="$appid" :game="$game" :title="$game->title" size="lg" :alt="__('game.cover_alt', ['title' => $game->title])" />
            </div>

            <div class="flex flex-col gap-4 sm:pb-2">
                <p class="text-sm font-medium text-muted"><x-decode :text="__('game.eyebrow')" /></p>
                <h1 class="font-display text-4xl font-semibold tracking-tight text-balance sm:text-5xl">{{ $game->title }}</h1>

                @if ($game->has_data)
                    <p class="max-w-2xl text-lg text-muted">
                        {{ $supported !== [] ? __('game.summary', ['technologies' => $technologyList]) : __('game.summary_none') }}
                    </p>
                @endif

                <div class="flex flex-wrap gap-2">
                    <a href="{{ $game->wikiUrl() }}" rel="noopener" class="focus-ring rounded border border-accent bg-accent px-4 py-2 text-sm font-semibold text-on-accent transition hover:brightness-110">
                        {{ __('game.wiki_link') }} <span class="font-narrow">↗</span>
                    </a>
                    @if ($appid)
                        <a href="https://store.steampowered.com/app/{{ $appid }}/" rel="noopener" class="focus-ring rounded border border-line-strong px-4 py-2 text-sm text-muted transition hover:border-fg hover:text-fg">
                            {{ __('game.store_link') }} ↗
                        </a>
                    @endif
                </div>
            </div>
            </div>
        </header>

        @if ($game->has_data)
            <section aria-labelledby="technologies-title" class="flex flex-col gap-4">
                <h2 id="technologies-title" class="label">{{ __('game.technologies.title') }}</h2>
                <div class="grid gap-px border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (Technology::byVendor() as $vendor => $technologies)
                        <div class="flex flex-col gap-3 bg-panel p-4" data-reveal style="--delay: {{ $loop->index * 90 }}ms">
                            <h3 class="label">{{ __('technologies.group.'.$vendor) }}</h3>
                            <ul class="flex flex-col gap-2.5">
                                @foreach ($technologies as $technology)
                                    @php
                                        $support = $game->{$technology->value};
                                        $version = $support === Support::Yes ? $classifier->latestVersion($game->upscaling_tech, $technology) : null;
                                    @endphp
                                    <li class="flex items-center justify-between gap-3">
                                        <span class="text-sm">{{ __('technologies.description.'.$technology->value) }}</span>
                                        <x-tech-pill :technology="$technology" :support="$support" :version="$version" />
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="raw-title" class="flex flex-col gap-4">
                <h2 id="raw-title" class="label">{{ __('game.raw.title') }}</h2>
                <dl class="grid gap-x-8 gap-y-3 font-narrow text-sm sm:grid-cols-[auto_1fr]">
                    @foreach (['upscaling' => $game->upscaling_tech, 'frame_gen' => $game->frame_gen_tech] as $field => $value)
                        <dt class="text-muted">{{ __('game.raw.'.$field) }}</dt>
                        <dd @class(['text-muted' => ! $value])>{{ $rawList($value) ?? __('game.raw.empty') }}</dd>
                    @endforeach
                </dl>
            </section>
            @if ($changes->isNotEmpty())
                <section aria-labelledby="changes-title" class="flex flex-col gap-4">
                    <h2 id="changes-title" class="label">{{ __('game.changes') }}</h2>
                    <ol class="flex flex-col gap-2 border-l border-line pl-4">
                        @foreach ($changes as $change)
                            <li class="flex flex-wrap items-center gap-3">
                                <time datetime="{{ $change->detected_at->toDateString() }}" class="font-narrow text-xs text-muted">{{ $change->detected_at->isoFormat('LL') }}</time>
                                <x-change-label :change="$change" />
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        @else
            <section class="frame flex flex-col items-start gap-3 p-6" aria-labelledby="no-data-title">
                <h2 id="no-data-title" class="font-display text-xl font-semibold">{{ __('game.no_data.title') }}</h2>
                <p class="max-w-2xl text-muted">{{ __('game.no_data.body') }}</p>
                <a href="{{ $game->wikiEditUrl() }}" rel="noopener" class="focus-ring rounded bg-accent px-4 py-2 text-sm font-semibold text-on-accent hover:brightness-110">
                    {{ __('game.no_data.edit') }} <span class="font-narrow">↗</span>
                </a>
            </section>
        @endif

        <footer class="flex flex-col gap-3 border-t border-line pt-5 text-sm text-muted">
            @if ($game->has_data)
                <x-support-legend />
                <p>{{ __('game.missing') }} <a href="{{ $game->wikiEditUrl() }}" rel="noopener" class="focus-ring text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent">{{ __('game.edit') }} ↗</a></p>
            @endif
            <p>
                {!! __('game.credit', [
                    'pcgw' => '<a href="'.e($game->wikiUrl()).'" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener">PCGamingWiki</a>',
                    'license' => '<a href="https://creativecommons.org/licenses/by-nc-sa/3.0/" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener license">CC BY-NC-SA 3.0</a>',
                    'date' => e($game->synced_at->isoFormat('LL')),
                ]) !!}
            </p>
        </footer>
    </article>
</x-layout>
