@php
    use App\Enums\Capability;
    use App\Enums\MatchSource;
    use App\Enums\Technology;
    use Illuminate\Support\Number;

    $percent = fn (int $count) => $stats['total'] > 0 ? round($count / $stats['total'] * 100) : 0;
    $sortIndicator = fn (string $column) => $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '';
    $ariaSort = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none';
    $hours = fn (int $minutes) => __('library.table.hours', ['hours' => Number::format($minutes / 60, maxPrecision: 1)]);
    // Desglose bajo cada capacidad; MFG queda fuera porque ya cuenta como DLSS FG.
    $breakdown = [
        Capability::Upscaling->value => [Technology::Dlss, Technology::Fsr, Technology::Xess],
        Capability::FrameGeneration->value => [Technology::DlssFg, Technology::FsrFg, Technology::XessFg],
        Capability::RayTracing->value => [],
    ];
@endphp

<div class="flex flex-col gap-8">
    {{-- Encabezado y selector "Mi GPU" --}}
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-col gap-3">
            <p class="text-sm font-medium text-muted"><x-decode :text="__('library.eyebrow')" /></p>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ $isWishlist ? __('library.wishlist.heading') : __('library.heading') }}</h1>
            <div class="flex border-b border-line text-sm" role="group" aria-label="{{ __('library.tabs.label') }}">
                @foreach (['library' => __('library.tabs.library'), 'wishlist' => __('library.tabs.wishlist')] as $option => $label)
                    <button
                        type="button"
                        wire:key="tab-{{ $option }}"
                        wire:click="showTab('{{ $option }}')"
                        aria-pressed="{{ $tab === $option ? 'true' : 'false' }}"
                        @class([
                            'focus-ring -mb-px border-b-2 px-3 py-2 transition',
                            'border-accent text-accent' => $tab === $option,
                            'border-transparent text-muted hover:text-fg' => $tab !== $option,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 font-narrow text-xs text-muted">
                <span><span aria-hidden="true">steamid=</span><span class="sr-only">SteamID </span><span class="text-fg">{{ $steamId }}</span></span>
                <button
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText(@js(route('library.show', $steamId))); copied = true; setTimeout(() => copied = false, 2000)"
                    class="focus-ring inline-flex items-center gap-1.5 border border-line-strong px-2 py-1 hover:border-accent hover:text-fg"
                >
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.6 3.4a3.5 3.5 0 0 1 4.95 4.95l-2.5 2.5a3.5 3.5 0 0 1-4.95 0 .75.75 0 1 1 1.06-1.06 2 2 0 0 0 2.83 0l2.5-2.5a2 2 0 0 0-2.83-2.83l-.88.88a.75.75 0 1 1-1.06-1.06l.88-.88Zm-5.2 5.2a3.5 3.5 0 0 1 4.95 0 .75.75 0 1 1-1.06 1.06 2 2 0 0 0-2.83 0l-2.5 2.5a2 2 0 1 0 2.83 2.83l.88-.88a.75.75 0 1 1 1.06 1.06l-.88.88a3.5 3.5 0 0 1-4.95-4.95l2.5-2.5Z" /></svg>
                    <span x-show="! copied">{{ __('library.share') }}</span>
                    <span x-show="copied" x-cloak role="status" class="text-accent">{{ __('library.copied') }}</span>
                </button>
            </div>
        </div>
    </div>

    @if ($isDemo)
        <p class="flex gap-3 border border-warn/50 bg-warn/10 px-4 py-3 text-sm" role="note">
            <span class="shrink-0 font-narrow text-xs leading-5 font-bold text-warn">DEMO</span>
            <span>{{ __('library.demo') }}</span>
        </p>
    @endif

    @if ($error === 'wishlist_private')
        <section class="frame flex flex-col gap-2 p-6" role="alert">
            <p class="font-narrow text-xs text-accent" aria-hidden="true">ERR_WISHLIST_EMPTY</p>
            <h2 class="font-display text-xl font-semibold">{{ __('library.wishlist.private.title') }}</h2>
            <p class="text-muted">{{ __('library.wishlist.private.body') }}</p>
        </section>
    @elseif ($error === 'private')
        <section class="frame flex flex-col gap-2 p-6" role="alert">
            <p class="font-narrow text-xs text-accent" aria-hidden="true">ERR_PRIVATE_PROFILE</p>
            <h2 class="font-display text-xl font-semibold">{{ __('library.errors.private.title') }}</h2>
            <p class="text-muted">{{ __('library.errors.private.body') }}</p>
            <p class="text-muted">{{ __('home.privacy_help.steps') }}</p>
        </section>
    @elseif ($error === 'unavailable')
        <section class="frame flex flex-col items-start gap-3 p-6" role="alert">
            <p class="font-narrow text-xs text-accent" aria-hidden="true">ERR_STEAM_TIMEOUT</p>
            <h2 class="font-display text-xl font-semibold">{{ __('library.errors.unavailable.title') }}</h2>
            <p class="text-muted">{{ __('library.errors.unavailable.body') }}</p>
            <button type="button" wire:click="$refresh" class="focus-ring rounded bg-accent px-4 py-2 text-sm font-semibold text-on-accent hover:brightness-110">
                {{ __('library.errors.unavailable.retry') }} ↻
            </button>
        </section>
    @else
        {{-- Estadísticas: total con cobertura de la wiki y una celda por capacidad. --}}
        <section aria-label="{{ __('library.stats.label') }}">
            <dl class="grid grid-cols-2 gap-px border border-line bg-line lg:grid-cols-4">
                <div class="col-span-2 flex flex-col justify-between gap-4 bg-panel p-4 lg:col-span-1">
                    <dt class="label">{{ __('library.stats.total') }}</dt>
                    <dd class="flex flex-col gap-3">
                        <span class="font-display text-5xl leading-none font-semibold tabular-nums" data-count-up>{{ Number::format($stats['total']) }}</span>
                        <span class="flex flex-col gap-1.5 font-narrow text-xs text-muted">
                            <span title="{{ __('library.stats.with_data_help') }}">{{ __('library.stats.with_data', ['count' => Number::format($stats['with_data'])]) }}</span>
                            <span class="meter" aria-hidden="true"><span class="meter-fill" style="width: {{ $percent($stats['with_data']) }}%"></span></span>
                        </span>
                    </dd>
                </div>

                @foreach (Capability::cases() as $capability)
                    @php $count = $stats['capabilities'][$capability->value]; @endphp
                    <div wire:key="stat-{{ $capability->value }}" @class(['flex flex-col gap-3 bg-panel p-4', 'col-span-2 sm:col-span-1' => $loop->last])>
                        <dt class="flex flex-col gap-0.5">
                            <span class="label">{{ __('library.capability.'.$capability->value.'.label') }}</span>
                            <span @class(['font-narrow text-[0.65rem]', 'text-accent' => $gpuProfile, 'text-muted' => ! $gpuProfile])>
                                {{ $gpuProfile ? __('library.stats.on_gpu', ['gpu' => $gpuProfile->label()]) : __('library.stats.any_vendor') }}
                            </span>
                        </dt>
                        <dd class="flex flex-col gap-2">
                            <span class="font-display text-4xl leading-none font-semibold tabular-nums" data-count-up>{{ Number::format($count) }}</span>
                            <span class="meter" aria-hidden="true"><span class="meter-fill" style="width: {{ $percent($count) }}%"></span></span>
                            @if ($breakdown[$capability->value] !== [])
                                <span class="flex flex-wrap gap-x-3 font-narrow text-[0.7rem] text-muted">
                                    @foreach ($breakdown[$capability->value] as $technology)
                                        <span>{{ __('technologies.label.'.$technology->value) }} <span class="text-fg tabular-nums">{{ Number::format($stats['technologies'][$technology->value]) }}</span></span>
                                    @endforeach
                                </span>
                            @else
                                <span class="font-narrow text-[0.7rem] text-muted">{{ __('library.stats.games_with', ['count' => Number::format($count), 'total' => Number::format($stats['total'])]) }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-8">
        <x-technology-filters :filter="$filter" :has-active-filters="$hasActiveFilters" :gpu="$gpu" :gpu-model="$gpuModel" />

        {{-- Cuadrícula o tabla. En la tabla, `all` muestra las columnas por marca además de "En tu GPU"; se recuerda en el navegador. --}}
        <div
            class="flex min-w-0 flex-col gap-4"
            x-data="{
                all: false,
                init() { try { this.all = localStorage.getItem('renderdex.all-technologies') === '1' } catch (e) {} },
                toggleAll() {
                    this.all = ! this.all;
                    try { localStorage.setItem('renderdex.all-technologies', this.all ? '1' : '0') } catch (e) {}
                },
            }"
        >
            <x-name-search :placeholder="__('library.search.placeholder')" />

            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                {{-- En la tabla también se ordena desde los encabezados; en la cuadrícula, solo desde aquí. --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2" role="group" aria-label="{{ __('library.table.sort_label') }}">
                    <span class="label">{{ __('library.table.sort_label') }}</span>
                    @foreach (array_filter(['name' => __('library.table.name'), 'playtime' => $isWishlist ? null : __('library.table.playtime'), 'technologies' => __('library.table.sort_technologies')]) as $column => $label)
                        <button
                            type="button"
                            wire:key="sort-{{ $column }}"
                            wire:click="sortBy('{{ $column }}')"
                            aria-pressed="{{ $sort === $column ? 'true' : 'false' }}"
                            @class(['focus-ring inline-flex items-center gap-1 hover:text-accent', 'text-accent' => $sort === $column, 'text-muted' => $sort !== $column])
                        >
                            {{ $label }} <span aria-hidden="true">{{ $sortIndicator($column) ?: '↕' }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-4">
                    @if ($gpuProfile && $layout === 'table')
                        <button type="button" x-on:click="toggleAll()" x-bind:aria-pressed="all ? 'true' : 'false'" class="focus-ring hidden text-muted hover:text-accent lg:inline-flex">
                            <span x-show="! all">{{ __('library.gpu.show_all') }} +</span>
                            <span x-show="all" x-cloak>{{ __('library.gpu.show_less') }} −</span>
                        </button>
                    @endif
                    <x-view-switcher :layout="$layout" />
                </div>
            </div>

            @if ($layout === 'grid')
                <div class="relative" wire:loading.class="opacity-70">
                    <div wire:loading.delay class="loading-bar z-10" aria-hidden="true"></div>
                    @if ($games->isEmpty())
                        <p class="frame px-3 py-14 text-center font-narrow text-sm text-muted">
                            <span class="text-accent" aria-hidden="true">∅ </span>{{ $stats['total'] === 0 ? __('library.empty') : __('library.no_results') }}
                        </p>
                    @else
                        <ul class="grid grid-cols-2 gap-x-4 gap-y-7 sm:grid-cols-3 md:grid-cols-4 lg:gap-x-5">
                            @foreach ($games as $entry)
                                <li wire:key="card-{{ $entry->owned->appid }}" class="animate-rise" style="--delay: {{ $loop->index * 25 }}ms">
                                    <x-game-card :game="$entry->game" :appid="$entry->owned->appid" :name="$entry->owned->name" :gpu-profile="$gpuProfile">
                                        <x-slot:meta>
                                            @if ($isWishlist)
                                                <a href="https://store.steampowered.com/app/{{ $entry->owned->appid }}/" rel="noopener" aria-label="{{ __('catalog.store_link_label', ['name' => $entry->owned->name]) }}" class="focus-ring hover:text-accent">{{ __('catalog.store_link') }} ↗</a>
                                            @elseif ($entry->owned->playtimeMinutes > 0)
                                                <span>{{ $hours($entry->owned->playtimeMinutes) }}</span>
                                            @endif
                                            @if ($entry->game && $recentlyChanged->has($entry->game->id))
                                                <a href="{{ route('changes.index') }}" title="{{ __('changes.badge_help') }}" class="focus-ring font-semibold text-accent">● {{ __('changes.badge') }}</a>
                                            @endif
                                            @if ($entry->matchedBy === MatchSource::Title)
                                                <span class="text-warn" title="{{ __('library.matched_by_title_help') }}">≈ {{ __('library.matched_by_title') }}</span>
                                            @endif
                                        </x-slot:meta>
                                    </x-game-card>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @else
            <div class="frame relative overflow-hidden" wire:loading.class="opacity-70">
                <div wire:loading.delay class="loading-bar z-10" aria-hidden="true"></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">{{ __('library.table.caption') }}</caption>
                        <thead class="border-b border-line-strong text-left">
                            <tr>
                                <th scope="col" class="py-3 pr-1 pl-3 sm:pl-4"><span class="sr-only">{{ __('library.table.cover') }}</span></th>
                                @foreach ($isWishlist ? ['name' => __('library.table.name')] : ['name' => __('library.table.name'), 'playtime' => __('library.table.playtime')] as $column => $label)
                                    <th scope="col" aria-sort="{{ $ariaSort($column) }}" @class(['px-3 py-3 font-normal', 'hidden text-right sm:table-cell' => $column === 'playtime'])>
                                        <button type="button" wire:click="sortBy('{{ $column }}')" class="focus-ring label inline-flex items-center gap-1 hover:text-fg!" aria-label="{{ __('library.table.sort_by', ['column' => $label]) }}">
                                            {{ $label }} <span aria-hidden="true" class="text-accent">{{ $sortIndicator($column) ?: '↕' }}</span>
                                        </button>
                                    </th>
                                @endforeach
                                <x-technology-headers :gpu-profile="$gpuProfile" :highlighted-vendor="$highlightedVendor" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($games as $entry)
                                <tr wire:key="game-{{ $entry->owned->appid }}" class="animate-rise transition-colors hover:bg-raised/60 hover:shadow-[inset_2px_0_0_var(--rd-accent)]" style="--delay: {{ $loop->index * 18 }}ms">
                                    <td class="py-3 pr-1 pl-3 align-top sm:pl-4">
                                        @if ($entry->game)
                                            {{-- El título ya enlaza a la ficha: la portada es un atajo para el mouse. --}}
                                            <a href="{{ $entry->game->url() }}" tabindex="-1" aria-hidden="true" class="block transition hover:brightness-110">
                                                <x-game-cover :appid="$entry->owned->appid" :game="$entry->game" :title="$entry->owned->name" />
                                            </a>
                                        @else
                                            <x-game-cover :appid="$entry->owned->appid" :title="$entry->owned->name" />
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex flex-col gap-0.5">
                                                @if ($entry->game)
                                                    <a href="{{ $entry->game->url() }}" class="focus-ring self-start font-medium decoration-accent underline-offset-4 hover:text-accent hover:underline">
                                                        {{ $entry->owned->name }}
                                                    </a>
                                                @else
                                                    <span class="font-medium">{{ $entry->owned->name }}</span>
                                                @endif
                                                <span class="flex flex-wrap gap-x-3 font-narrow text-[0.7rem] text-muted">
                                                    @if ($isWishlist)
                                                        <a href="https://store.steampowered.com/app/{{ $entry->owned->appid }}/" rel="noopener" aria-label="{{ __('catalog.store_link_label', ['name' => $entry->owned->name]) }}" class="focus-ring hover:text-accent">{{ __('catalog.store_link') }} ↗</a>
                                                    @else
                                                        <span class="sm:hidden">{{ $hours($entry->owned->playtimeMinutes) }}</span>
                                                    @endif
                                                    @if ($entry->game)
                                                        @if ($recentlyChanged->has($entry->game->id))
                                                            <a href="{{ route('changes.index') }}" title="{{ __('changes.badge_help') }}" class="focus-ring font-semibold text-accent">● {{ __('changes.badge') }}</a>
                                                        @endif
                                                        <a href="{{ $entry->game->wikiUrl() }}" rel="noopener" aria-label="{{ __('library.wiki_link', ['name' => $entry->owned->name]) }}" class="focus-ring hover:text-accent">{{ __('catalog.wiki_link') }} ↗</a>
                                                        @if ($entry->matchedBy === MatchSource::Title)
                                                            <span class="text-warn" title="{{ __('library.matched_by_title_help') }}">≈ {{ __('library.matched_by_title') }}</span>
                                                        @endif
                                                    @endif
                                                </span>
                                            </div>

                                            <x-technology-compact :game="$entry->game" :name="$entry->owned->name" :gpu-profile="$gpuProfile" :highlighted-vendor="$highlightedVendor" />
                                        </div>
                                    </td>
                                    @unless ($isWishlist)
                                        <td class="hidden px-3 py-3 text-right align-top font-narrow text-xs text-muted tabular-nums sm:table-cell lg:align-middle">
                                            {{ Number::format($entry->owned->playtimeMinutes / 60, maxPrecision: 1) }}
                                        </td>
                                    @endunless
                                    <x-technology-cells :game="$entry->game" :row-key="$entry->owned->appid" :name="$entry->owned->name" :gpu-profile="$gpuProfile" :highlighted-vendor="$highlightedVendor" />
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($isWishlist ? 2 : 3) + ($gpuProfile ? 1 : 0) + count(Technology::byVendor()) }}" class="px-3 py-14 text-center font-narrow text-sm text-muted">
                                        <span class="text-accent" aria-hidden="true">∅ </span>{{ $stats['total'] === 0 ? __('library.empty') : __('library.no_results') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{ $games->links() }}
        </div>
        </div>

        @if ($missingData->isNotEmpty())
            <section class="frame flex flex-col gap-4 p-5" aria-labelledby="missing-data-title">
                <div class="flex flex-col gap-1">
                    <h2 id="missing-data-title" class="font-display text-lg font-semibold">{{ __('library.missing.title') }}</h2>
                    <p class="max-w-2xl text-sm text-muted">{{ __('library.missing.body') }}</p>
                </div>
                <ol class="flex flex-col divide-y divide-line">
                    @foreach ($missingData as $entry)
                        <li wire:key="missing-{{ $entry->owned->appid }}" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2">
                            <span class="flex items-baseline gap-3">
                                <span class="font-narrow text-xs text-accent" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                                <span class="text-sm font-medium">{{ $entry->owned->name }}</span>
                                <span class="font-narrow text-xs text-muted">{{ $hours($entry->owned->playtimeMinutes) }}</span>
                            </span>
                            <x-no-data :game="$entry->game" :name="$entry->owned->name" />
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <div class="flex flex-col gap-3 border-t border-line pt-5 text-sm text-muted">
            <x-support-legend />
            <p>{{ $isWishlist ? __('library.wishlist.note') : __('home.privacy_help.family_sharing') }}</p>
        </div>
    @endif
</div>
