@inject('classifier', \App\Services\FeatureClassifier::class)

@php
    use App\Enums\Support;
    use App\Enums\Technology;
    use Illuminate\Support\Arr;

    $technologyList = Arr::join($supported, ', ', __('game.list_and'));
    $metaTitle = __('game.meta_title', ['title' => $game->title]);
    $metaDescription = $supported !== []
        ? __('game.meta_description', ['title' => $game->title, 'technologies' => $technologyList])
        : __('game.meta_description_none', ['title' => $game->title]);
    $headerImage = $appid ? "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$appid}/header.jpg" : null;
    $rawList = fn (?string $list) => $list ? collect(explode(',', $list))->map(fn (string $value) => trim($value))->implode(', ') : null;
@endphp

<x-layout :title="$metaTitle" :description="$metaDescription" :image="$headerImage" :canonical="$game->url()" :noindex="! $game->has_data">
    <article class="flex flex-col gap-10">
        <nav aria-label="{{ __('game.breadcrumb') }}" class="font-mono text-xs text-muted">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ route('catalog') }}" class="focus-ring uppercase hover:text-accent">{{ __('layout.header.catalog') }}</a></li>
                <li aria-hidden="true" class="text-accent">›</li>
                <li><span aria-current="page" class="text-fg">{{ $game->title }}</span></li>
            </ol>
        </nav>

        <header class="grid items-start gap-8 lg:grid-cols-[1fr_auto]">
            <div class="flex flex-col gap-4">
                <p class="font-mono text-xs text-muted uppercase"><span class="text-accent" aria-hidden="true">//</span> <x-decode :text="__('game.eyebrow')" /></p>
                <h1 class="font-display text-4xl font-semibold tracking-tight text-balance sm:text-5xl">{{ $game->title }}</h1>

                @if ($game->has_data)
                    <p class="max-w-2xl text-lg text-muted">
                        {{ $supported !== [] ? __('game.summary', ['technologies' => $technologyList]) : __('game.summary_none') }}
                    </p>
                @endif

                <div class="flex flex-wrap gap-2">
                    <a href="{{ $game->wikiUrl() }}" rel="noopener" class="focus-ring border border-accent px-4 py-2 font-mono text-xs font-bold text-accent uppercase transition hover:bg-accent hover:text-on-accent">
                        {{ __('game.wiki_link') }} ↗
                    </a>
                    @if ($appid)
                        <a href="https://store.steampowered.com/app/{{ $appid }}/" rel="noopener" class="focus-ring border border-line-strong px-4 py-2 font-mono text-xs text-muted uppercase transition hover:border-fg hover:text-fg">
                            {{ __('game.store_link') }} ↗
                        </a>
                    @endif
                </div>
            </div>

            @if ($headerImage)
                <div class="frame p-1.5">
                    <img
                        src="{{ $headerImage }}"
                        alt="{{ __('game.cover_alt', ['title' => $game->title]) }}"
                        width="460"
                        height="215"
                        class="block aspect-[460/215] w-full max-w-[460px] bg-raised object-cover"
                        onerror="this.closest('.frame').remove()"
                    >
                </div>
            @endif
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
                <dl class="grid gap-x-8 gap-y-3 font-mono text-sm sm:grid-cols-[auto_1fr]">
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
                                <time datetime="{{ $change->detected_at->toDateString() }}" class="font-mono text-xs text-muted">{{ $change->detected_at->isoFormat('LL') }}</time>
                                <x-change-label :change="$change" />
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        @else
            <section class="frame flex flex-col items-start gap-3 p-6" aria-labelledby="no-data-title">
                <p class="font-mono text-xs text-accent" aria-hidden="true" data-decode>ERR_NO_RENDER_DATA</p>
                <h2 id="no-data-title" class="font-display text-xl font-semibold">{{ __('game.no_data.title') }}</h2>
                <p class="max-w-2xl text-muted">{{ __('game.no_data.body') }}</p>
                <a href="{{ $game->wikiEditUrl() }}" rel="noopener" class="focus-ring bg-accent px-4 py-2 font-mono text-sm font-bold text-on-accent uppercase hover:brightness-110">
                    {{ __('game.no_data.edit') }} ↗
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
