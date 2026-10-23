@php
    use App\Enums\MatchSource;
    use App\Enums\Support;
    use App\Enums\Technology;
    use App\Enums\Vendor;
    use Illuminate\Support\Number;

    $groups = Technology::byVendor();
    $isHighlighted = fn (string $vendor) => $highlightedVendor?->value === $vendor;
    // En pantallas chicas, con una marca elegida, solo se muestran sus tecnologías y Ray Tracing.
    $compactGroups = $highlightedVendor ? array_intersect_key($groups, array_flip([$highlightedVendor->value, Vendor::All->value])) : $groups;
    $percent = fn (int $count) => $stats['total'] > 0 ? round($count / $stats['total'] * 100) : 0;
    $sortIndicator = fn (string $column) => $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '';
    $ariaSort = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none';
    $hours = fn (int $minutes) => __('library.table.hours', ['hours' => Number::format($minutes / 60, maxPrecision: 1)]);
@endphp

<div
    class="flex flex-col gap-8"
    x-data="{
        init() {
            let saved = null;
            try { saved = localStorage.getItem('renderdex.gpu') } catch (e) {}
            if (saved && saved !== $wire.gpu) {
                $wire.selectGpu(saved, {{ $activeFilters === [] ? 'true' : 'false' }});
            }
        },
        chooseGpu(gpu) {
            try { localStorage.setItem('renderdex.gpu', gpu) } catch (e) {}
            $wire.selectGpu(gpu);
        },
    }"
>
    {{-- Encabezado y selector "Mi GPU" --}}
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-col gap-3">
            <p class="font-mono text-xs text-muted uppercase"><span class="text-accent" aria-hidden="true">//</span> {{ __('library.eyebrow') }}</p>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ __('library.heading') }}</h1>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 font-mono text-xs text-muted">
                <span><span class="text-line-strong">steamid=</span>{{ $steamId }}</span>
                <button
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText(@js(route('library.show', $steamId))); copied = true; setTimeout(() => copied = false, 2000)"
                    class="focus-ring inline-flex items-center gap-1.5 border border-line-strong px-2 py-1 uppercase hover:border-accent hover:text-fg"
                >
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.6 3.4a3.5 3.5 0 0 1 4.95 4.95l-2.5 2.5a3.5 3.5 0 0 1-4.95 0 .75.75 0 1 1 1.06-1.06 2 2 0 0 0 2.83 0l2.5-2.5a2 2 0 0 0-2.83-2.83l-.88.88a.75.75 0 1 1-1.06-1.06l.88-.88Zm-5.2 5.2a3.5 3.5 0 0 1 4.95 0 .75.75 0 1 1-1.06 1.06 2 2 0 0 0-2.83 0l-2.5 2.5a2 2 0 1 0 2.83 2.83l.88-.88a.75.75 0 1 1 1.06 1.06l-.88.88a3.5 3.5 0 0 1-4.95-4.95l2.5-2.5Z" /></svg>
                    <span x-show="! copied">{{ __('library.share') }}</span>
                    <span x-show="copied" x-cloak role="status" class="text-accent">{{ __('library.copied') }}</span>
                </button>
            </div>
        </div>

        @unless ($error)
            <fieldset class="flex flex-col gap-2">
                <legend class="label mb-2">{{ __('library.gpu.label') }}</legend>
                <div class="grid grid-cols-4 border border-line-strong bg-panel">
                    @foreach (['nvidia', 'amd', 'intel', 'all'] as $option)
                        <button
                            type="button"
                            wire:key="gpu-{{ $option }}"
                            x-on:click="chooseGpu('{{ $option }}')"
                            aria-pressed="{{ $gpu === $option ? 'true' : 'false' }}"
                            @class([
                                'focus-ring px-3 py-2 font-mono text-xs font-bold uppercase transition sm:px-5',
                                'border-l border-line-strong' => ! $loop->first,
                                'bg-accent text-on-accent' => $gpu === $option,
                                'text-muted hover:bg-raised hover:text-fg' => $gpu !== $option,
                            ])
                        >
                            {{ __('technologies.vendor.'.$option) }}
                        </button>
                    @endforeach
                </div>
                <p class="text-xs text-muted">{{ __('library.gpu.help') }}</p>
            </fieldset>
        @endunless
    </div>

    @if ($isDemo)
        <p class="flex gap-3 border border-warn/50 bg-warn/10 px-4 py-3 text-sm" role="note">
            <span class="shrink-0 font-mono text-xs leading-5 font-bold text-warn">DEMO</span>
            <span>{{ __('library.demo') }}</span>
        </p>
    @endif

    @if ($error === 'private')
        <section class="frame flex flex-col gap-2 p-6" role="alert">
            <p class="font-mono text-xs text-accent" aria-hidden="true">ERR_PRIVATE_PROFILE</p>
            <h2 class="font-display text-xl font-semibold">{{ __('library.errors.private.title') }}</h2>
            <p class="text-muted">{{ __('library.errors.private.body') }}</p>
            <p class="text-muted">{{ __('home.privacy_help.steps') }}</p>
        </section>
    @elseif ($error === 'unavailable')
        <section class="frame flex flex-col items-start gap-3 p-6" role="alert">
            <p class="font-mono text-xs text-accent" aria-hidden="true">ERR_STEAM_TIMEOUT</p>
            <h2 class="font-display text-xl font-semibold">{{ __('library.errors.unavailable.title') }}</h2>
            <p class="text-muted">{{ __('library.errors.unavailable.body') }}</p>
            <button type="button" wire:click="$refresh" class="focus-ring bg-accent px-4 py-2 font-mono text-sm font-bold text-on-accent uppercase hover:brightness-110">
                {{ __('library.errors.unavailable.retry') }} ↻
            </button>
        </section>
    @else
        {{-- Estadísticas: una sola lectura, dividida en celdas como un panel de instrumentos. --}}
        <section aria-label="{{ __('library.stats.label') }}">
            <dl class="grid grid-cols-2 gap-px border border-line bg-line lg:grid-cols-5">
                <div class="col-span-2 flex flex-col justify-between gap-4 bg-panel p-4 lg:col-span-1">
                    <dt class="label">{{ __('library.stats.total') }}</dt>
                    <dd class="flex flex-col gap-3">
                        <span class="font-display text-5xl leading-none font-semibold tabular-nums">{{ Number::format($stats['total']) }}</span>
                        <span class="flex flex-col gap-1.5 font-mono text-xs text-muted">
                            {{ __('library.stats.with_wiki_page', ['count' => Number::format($stats['with_wiki_page'])]) }}
                            <span class="meter" aria-hidden="true"><span class="meter-fill" style="width: {{ $percent($stats['with_wiki_page']) }}%"></span></span>
                        </span>
                    </dd>
                </div>

                @foreach ($groups as $vendor => $groupTechnologies)
                    <div wire:key="stat-{{ $vendor }}" @class(['relative flex flex-col gap-3 bg-panel p-4', 'shadow-[inset_0_2px_0_var(--rd-accent)]' => $isHighlighted($vendor)])>
                        <dt @class(['label', 'text-accent!' => $isHighlighted($vendor)])>
                            {{ __('technologies.group.'.$vendor) }}
                        </dt>
                        <dd class="flex flex-col gap-3">
                            @if (count($groupTechnologies) === 1)
                                {{-- Una sola tecnología (Ray Tracing): número grande, sin repetir el nombre. --}}
                                @php $count = $stats['technologies'][$groupTechnologies[0]->value]; @endphp
                                <span class="font-display text-4xl leading-none font-semibold tabular-nums">{{ Number::format($count) }}</span>
                                <span class="flex flex-col gap-1.5 font-mono text-xs text-muted">
                                    {{ __('library.stats.games_with', ['count' => Number::format($count), 'total' => Number::format($stats['total'])]) }}
                                    <span class="meter" aria-hidden="true"><span class="meter-fill" style="width: {{ $percent($count) }}%"></span></span>
                                </span>
                            @else
                                @foreach ($groupTechnologies as $technology)
                                    @php $count = $stats['technologies'][$technology->value]; @endphp
                                    <span class="flex flex-col gap-1">
                                        <span class="flex items-baseline justify-between gap-2 font-mono text-xs">
                                            <span class="text-muted">{{ __('technologies.label.'.$technology->value) }}</span>
                                            <span class="font-bold tabular-nums">{{ Number::format($count) }}</span>
                                        </span>
                                        <span class="meter" aria-hidden="true"><span class="meter-fill" style="width: {{ $percent($count) }}%"></span></span>
                                    </span>
                                @endforeach
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Filtros y búsqueda --}}
        <section class="flex flex-col gap-5 border-y border-line py-5 lg:flex-row lg:items-end lg:justify-between">
            <fieldset class="flex flex-col gap-2">
                <legend class="label mb-2">{{ __('library.filters.label') }}</legend>
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach (Technology::cases() as $technology)
                        @php $isActive = in_array($technology->value, $activeFilters, true); @endphp
                        <button
                            type="button"
                            wire:key="filter-{{ $technology->value }}"
                            wire:click="toggleFilter('{{ $technology->value }}')"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                            @class([
                                'focus-ring inline-flex items-center gap-1.5 border px-2.5 py-1 font-mono text-xs transition',
                                'border-accent bg-accent/12 text-accent' => $isActive,
                                'border-line-strong text-muted hover:border-fg hover:text-fg' => ! $isActive,
                            ])
                        >
                            {{-- Casilla: refuerza el estado sin depender del color. --}}
                            <span @class(['size-2 border', 'border-accent bg-accent' => $isActive, 'border-current' => ! $isActive]) aria-hidden="true"></span>
                            {{ __('technologies.label.'.$technology->value) }}
                        </button>
                    @endforeach

                    @if ($activeFilters !== [] || $search !== '')
                        <button type="button" wire:click="clearFilters" class="focus-ring px-2 py-1 font-mono text-xs text-accent underline underline-offset-4">
                            {{ __('library.filters.clear') }}
                        </button>
                    @endif
                </div>
            </fieldset>

            <div
                class="flex flex-col gap-2 lg:w-80"
                x-on:keydown.window="if ($event.key === '/' && ! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { $event.preventDefault(); $refs.search.focus() }"
            >
                <label for="library-search" class="label">{{ __('library.search.label') }}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 font-mono text-sm text-accent" aria-hidden="true">›</span>
                    <input
                        id="library-search"
                        type="search"
                        x-ref="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('library.search.placeholder') }}"
                        aria-keyshortcuts="/"
                        class="w-full border border-line-strong bg-canvas py-2 pr-10 pl-7 font-mono text-sm placeholder:text-muted/70 focus:border-accent focus:outline-none"
                    >
                    <kbd class="pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 border border-line-strong px-1.5 font-mono text-[0.65rem] text-muted" aria-hidden="true">/</kbd>
                </div>
            </div>
        </section>

        {{-- Tabla --}}
        <div class="frame overflow-hidden" wire:loading.class="opacity-60">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="sr-only">{{ __('library.table.caption') }}</caption>
                    <thead class="border-b border-line-strong text-left">
                        <tr>
                            <th scope="col" class="hidden py-3 pr-1 pl-4 sm:table-cell"><span class="sr-only">{{ __('library.table.cover') }}</span></th>
                            @foreach (['name' => __('library.table.name'), 'playtime' => __('library.table.playtime')] as $column => $label)
                                <th scope="col" aria-sort="{{ $ariaSort($column) }}" @class(['px-3 py-3 font-normal', 'hidden text-right sm:table-cell' => $column === 'playtime'])>
                                    <button type="button" wire:click="sortBy('{{ $column }}')" class="focus-ring label inline-flex items-center gap-1 hover:text-fg!" aria-label="{{ __('library.table.sort_by', ['column' => $label]) }}">
                                        {{ $label }} <span aria-hidden="true" class="text-accent">{{ $sortIndicator($column) ?: '↕' }}</span>
                                    </button>
                                </th>
                            @endforeach
                            @foreach ($groups as $vendor => $groupTechnologies)
                                <th scope="col" wire:key="th-{{ $vendor }}" @class([
                                    'label hidden px-3 py-3 font-normal lg:table-cell',
                                    'bg-accent/8 text-accent! shadow-[inset_0_2px_0_var(--rd-accent)]' => $isHighlighted($vendor),
                                ])>
                                    {{ __('technologies.group.'.$vendor) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($games as $entry)
                            <tr wire:key="game-{{ $entry->owned->appid }}" class="transition-colors hover:bg-raised/60">
                                <td class="hidden py-3 pr-1 pl-4 align-top sm:table-cell lg:align-middle">
                                    <img
                                        src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{{ $entry->owned->appid }}/capsule_184x69.jpg"
                                        alt=""
                                        width="92"
                                        height="35"
                                        loading="lazy"
                                        onerror="this.onerror = null; this.src = '{{ asset('images/cover-placeholder.svg') }}'"
                                        class="h-[35px] w-[92px] max-w-none bg-raised object-cover ring-1 ring-line"
                                    >
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex flex-col gap-0.5">
                                            @if ($entry->game)
                                                <a href="https://www.pcgamingwiki.com/w/index.php?curid={{ $entry->game->pcgw_page_id }}" rel="noopener" title="{{ __('library.wiki_link', ['name' => $entry->owned->name]) }}" class="focus-ring self-start font-medium decoration-accent underline-offset-4 hover:text-accent hover:underline">
                                                    {{ $entry->owned->name }}
                                                </a>
                                            @else
                                                <span class="font-medium">{{ $entry->owned->name }}</span>
                                            @endif
                                            <span class="flex flex-wrap gap-x-3 font-mono text-[0.7rem] text-muted">
                                                <span class="sm:hidden">{{ $hours($entry->owned->playtimeMinutes) }}</span>
                                                @if (! $entry->game)
                                                    <span>{{ __('library.no_wiki_page') }}</span>
                                                @elseif ($entry->matchedBy === MatchSource::Title)
                                                    <span class="text-warn" title="{{ __('library.matched_by_title_help') }}">≈ {{ __('library.matched_by_title') }}</span>
                                                @endif
                                            </span>
                                        </div>

                                        {{-- Sin espacio para las columnas por marca: las píldoras van bajo el nombre. --}}
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 lg:hidden">
                                            @foreach ($compactGroups as $vendor => $groupTechnologies)
                                                <x-tech-pills wire:key="compact-{{ $entry->owned->appid }}-{{ $vendor }}" :game="$entry->game" :technologies="$groupTechnologies" />
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden px-3 py-3 text-right align-top font-mono text-xs text-muted tabular-nums sm:table-cell lg:align-middle">
                                    {{ Number::format($entry->owned->playtimeMinutes / 60, maxPrecision: 1) }}
                                </td>
                                @foreach ($groups as $vendor => $groupTechnologies)
                                    <td wire:key="cell-{{ $entry->owned->appid }}-{{ $vendor }}" @class([
                                        'hidden px-3 py-3 lg:table-cell',
                                        'bg-accent/5' => $isHighlighted($vendor),
                                    ])>
                                        <x-tech-pills :game="$entry->game" :technologies="$groupTechnologies" />
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + count($groups) }}" class="px-3 py-14 text-center font-mono text-sm text-muted">
                                    <span class="text-accent" aria-hidden="true">∅ </span>{{ $stats['total'] === 0 ? __('library.empty') : __('library.no_results') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $games->links() }}

        <div class="flex flex-col gap-3 border-t border-line pt-5 text-sm text-muted">
            <p class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <span class="label">{{ __('library.legend.label') }}</span>
                @foreach ([Support::Yes, Support::No, Support::Unknown] as $support)
                    <span class="inline-flex items-center gap-1.5">
                        {{-- El texto visible ya lo dice: la píldora se oculta a lectores de pantalla. --}}
                        <x-tech-pill :technology="Technology::Dlss" :support="$support" aria-hidden="true" />
                        {{ __('technologies.support.'.$support->value) }}
                    </span>
                @endforeach
                <span>{{ __('library.legend.unknown') }}</span>
            </p>
            <p>{{ __('home.privacy_help.family_sharing') }}</p>
        </div>
    @endif
</div>
