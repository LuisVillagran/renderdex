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
    class="flex flex-col gap-6"
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
        <div class="flex flex-col gap-2">
            <p class="font-mono text-xs tracking-widest text-accent uppercase">{{ __('library.eyebrow') }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ __('library.heading') }}</h1>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 font-mono text-xs text-muted">
                <span>SteamID {{ $steamId }}</span>
                <button
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText(@js(route('library.show', $steamId))); copied = true; setTimeout(() => copied = false, 2000)"
                    class="focus-ring inline-flex items-center gap-1.5 rounded-md px-2 py-1 ring-1 ring-line ring-inset hover:text-fg hover:ring-muted"
                >
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.6 3.4a3.5 3.5 0 0 1 4.95 4.95l-2.5 2.5a3.5 3.5 0 0 1-4.95 0 .75.75 0 1 1 1.06-1.06 2 2 0 0 0 2.83 0l2.5-2.5a2 2 0 0 0-2.83-2.83l-.88.88a.75.75 0 1 1-1.06-1.06l.88-.88Zm-5.2 5.2a3.5 3.5 0 0 1 4.95 0 .75.75 0 1 1-1.06 1.06 2 2 0 0 0-2.83 0l-2.5 2.5a2 2 0 1 0 2.83 2.83l.88-.88a.75.75 0 1 1 1.06 1.06l-.88.88a3.5 3.5 0 0 1-4.95-4.95l2.5-2.5Z" /></svg>
                    <span x-show="! copied">{{ __('library.share') }}</span>
                    <span x-show="copied" x-cloak role="status" class="text-accent">{{ __('library.copied') }}</span>
                </button>
            </div>
        </div>

        @unless ($error)
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-2 font-mono text-xs tracking-widest text-muted uppercase">{{ __('library.gpu.label') }}</legend>
                <div class="grid grid-cols-4 gap-1 rounded-xl border border-line bg-panel/80 p-1 backdrop-blur">
                    @foreach (['nvidia', 'amd', 'intel', 'all'] as $option)
                        <button
                            type="button"
                            wire:key="gpu-{{ $option }}"
                            x-on:click="chooseGpu('{{ $option }}')"
                            aria-pressed="{{ $gpu === $option ? 'true' : 'false' }}"
                            @class([
                                'focus-ring rounded-lg px-3 py-2 text-sm font-semibold transition sm:px-5',
                                'bg-brand shadow-lg shadow-accent-2/25' => $gpu === $option,
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
        <p class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-900 dark:text-amber-200" role="note">
            {{ __('library.demo') }}
        </p>
    @endif

    @if ($error === 'private')
        <section class="panel flex flex-col gap-2 p-6" role="alert">
            <h2 class="text-lg font-semibold">{{ __('library.errors.private.title') }}</h2>
            <p class="text-muted">{{ __('library.errors.private.body') }}</p>
            <p class="text-muted">{{ __('home.privacy_help.steps') }}</p>
        </section>
    @elseif ($error === 'unavailable')
        <section class="panel flex flex-col items-start gap-3 p-6" role="alert">
            <h2 class="text-lg font-semibold">{{ __('library.errors.unavailable.title') }}</h2>
            <p class="text-muted">{{ __('library.errors.unavailable.body') }}</p>
            <button type="button" wire:click="$refresh" class="focus-ring bg-brand rounded-lg px-4 py-2 font-semibold">
                {{ __('library.errors.unavailable.retry') }}
            </button>
        </section>
    @else
        {{-- Estadísticas --}}
        <section aria-label="{{ __('library.stats.label') }}">
            <dl class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div class="panel col-span-2 flex flex-col justify-between gap-4 p-5 lg:col-span-1">
                    <dt class="font-mono text-xs tracking-widest text-muted uppercase">{{ __('library.stats.total') }}</dt>
                    <dd class="flex flex-col gap-3">
                        <span class="text-4xl font-bold tabular-nums">{{ Number::format($stats['total']) }}</span>
                        <span class="flex flex-col gap-1.5 text-xs text-muted">
                            {{ __('library.stats.with_wiki_page', ['count' => Number::format($stats['with_wiki_page'])]) }}
                            <span class="h-1 rounded-full bg-raised" aria-hidden="true">
                                <span class="bg-brand block h-full rounded-full" style="width: {{ $percent($stats['with_wiki_page']) }}%"></span>
                            </span>
                        </span>
                    </dd>
                </div>

                @foreach ($groups as $vendor => $groupTechnologies)
                    <div wire:key="stat-{{ $vendor }}" @class(['panel flex flex-col gap-3 p-4', 'ring-1 ring-accent/60 glow' => $isHighlighted($vendor)])>
                        <dt @class(['font-mono text-xs tracking-widest uppercase', 'text-accent' => $isHighlighted($vendor), 'text-muted' => ! $isHighlighted($vendor)])>
                            {{ __('technologies.group.'.$vendor) }}
                        </dt>
                        <dd class="flex flex-col gap-2.5">
                            @if (count($groupTechnologies) === 1)
                                {{-- Una sola tecnología (Ray Tracing): número grande, sin repetir el nombre. --}}
                                @php $count = $stats['technologies'][$groupTechnologies[0]->value]; @endphp
                                <span class="text-3xl font-bold tabular-nums">{{ Number::format($count) }}</span>
                                <span class="flex flex-col gap-1.5 text-xs text-muted">
                                    {{ __('library.stats.games_with', ['count' => Number::format($count), 'total' => Number::format($stats['total'])]) }}
                                    <span class="h-1 rounded-full bg-raised" aria-hidden="true">
                                        <span class="bg-brand block h-full rounded-full" style="width: {{ $percent($count) }}%"></span>
                                    </span>
                                </span>
                            @else
                                @foreach ($groupTechnologies as $technology)
                                    @php $count = $stats['technologies'][$technology->value]; @endphp
                                    <span class="flex flex-col gap-1">
                                        <span class="flex items-baseline justify-between gap-2 text-sm">
                                            <span class="text-muted">{{ __('technologies.label.'.$technology->value) }}</span>
                                            <span class="font-semibold tabular-nums">{{ Number::format($count) }}</span>
                                        </span>
                                        <span class="h-1 rounded-full bg-raised" aria-hidden="true">
                                            <span class="bg-brand block h-full rounded-full" style="width: {{ $percent($count) }}%"></span>
                                        </span>
                                    </span>
                                @endforeach
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Filtros y búsqueda --}}
        <section class="panel flex flex-col gap-4 p-4 lg:flex-row lg:items-end lg:justify-between">
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-2 font-mono text-xs tracking-widest text-muted uppercase">{{ __('library.filters.label') }}</legend>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (Technology::cases() as $technology)
                        @php $isActive = in_array($technology->value, $activeFilters, true); @endphp
                        <button
                            type="button"
                            wire:key="filter-{{ $technology->value }}"
                            wire:click="toggleFilter('{{ $technology->value }}')"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                            @class([
                                'focus-ring rounded-full px-3 py-1 font-mono text-xs font-medium ring-1 ring-inset transition',
                                'bg-accent/15 text-accent ring-accent/50' => $isActive,
                                'text-muted ring-line hover:text-fg hover:ring-muted' => ! $isActive,
                            ])
                        >
                            {{ __('technologies.label.'.$technology->value) }}
                        </button>
                    @endforeach

                    @if ($activeFilters !== [] || $search !== '')
                        <button type="button" wire:click="clearFilters" class="focus-ring rounded-full px-2 py-1 text-xs text-accent underline underline-offset-2">
                            {{ __('library.filters.clear') }}
                        </button>
                    @endif
                </div>
            </fieldset>

            <div class="flex flex-col gap-2 lg:w-72">
                <label for="library-search" class="font-mono text-xs tracking-widest text-muted uppercase">{{ __('library.search.label') }}</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.32 9.88l3.4 3.4a.75.75 0 1 0 1.06-1.06l-3.4-3.4A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" /></svg>
                    <input
                        id="library-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('library.search.placeholder') }}"
                        class="w-full rounded-lg border border-line bg-canvas/60 py-2 pr-3 pl-9 text-sm placeholder:text-muted focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none"
                    >
                </div>
            </div>
        </section>

        {{-- Tabla --}}
        <div class="panel overflow-hidden" wire:loading.class="opacity-60">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="sr-only">{{ __('library.table.caption') }}</caption>
                    <thead class="border-b border-line text-left font-mono text-[0.7rem] tracking-wider text-muted uppercase">
                        <tr>
                            <th scope="col" class="hidden py-3 pr-1 pl-4 sm:table-cell"><span class="sr-only">{{ __('library.table.cover') }}</span></th>
                            @foreach (['name' => __('library.table.name'), 'playtime' => __('library.table.playtime')] as $column => $label)
                                <th scope="col" aria-sort="{{ $ariaSort($column) }}" @class(['px-3 py-3 font-medium', 'hidden text-right sm:table-cell' => $column === 'playtime'])>
                                    <button type="button" wire:click="sortBy('{{ $column }}')" class="focus-ring inline-flex items-center gap-1 rounded tracking-wider uppercase hover:text-fg" aria-label="{{ __('library.table.sort_by', ['column' => $label]) }}">
                                        {{ $label }} <span aria-hidden="true" class="text-accent">{{ $sortIndicator($column) }}</span>
                                    </button>
                                </th>
                            @endforeach
                            @foreach ($groups as $vendor => $groupTechnologies)
                                <th scope="col" wire:key="th-{{ $vendor }}" @class([
                                    'hidden px-3 py-3 font-medium lg:table-cell',
                                    'bg-accent/5 text-accent' => $isHighlighted($vendor),
                                ])>
                                    {{ __('technologies.group.'.$vendor) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/70">
                        @forelse ($games as $entry)
                            <tr wire:key="game-{{ $entry->owned->appid }}" class="transition-colors hover:bg-raised/50">
                                <td class="hidden py-3 pr-1 pl-4 align-top sm:table-cell lg:align-middle">
                                    <img
                                        src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{{ $entry->owned->appid }}/capsule_184x69.jpg"
                                        alt=""
                                        width="92"
                                        height="35"
                                        loading="lazy"
                                        onerror="this.onerror = null; this.src = '{{ asset('images/cover-placeholder.svg') }}'"
                                        class="h-[35px] w-[92px] max-w-none rounded-md bg-raised object-cover ring-1 ring-line"
                                    >
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex flex-col gap-0.5">
                                            @if ($entry->game)
                                                <a href="https://www.pcgamingwiki.com/w/index.php?curid={{ $entry->game->pcgw_page_id }}" rel="noopener" title="{{ __('library.wiki_link', ['name' => $entry->owned->name]) }}" class="focus-ring self-start rounded font-semibold hover:text-accent">
                                                    {{ $entry->owned->name }}
                                                </a>
                                            @else
                                                <span class="font-semibold">{{ $entry->owned->name }}</span>
                                            @endif
                                            <span class="flex flex-wrap gap-x-3 text-xs text-muted">
                                                <span class="font-mono sm:hidden">{{ $hours($entry->owned->playtimeMinutes) }}</span>
                                                @if (! $entry->game)
                                                    <span>{{ __('library.no_wiki_page') }}</span>
                                                @elseif ($entry->matchedBy === MatchSource::Title)
                                                    <span class="text-amber-700 dark:text-amber-400" title="{{ __('library.matched_by_title_help') }}">{{ __('library.matched_by_title') }}</span>
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
                                <td colspan="{{ 3 + count($groups) }}" class="px-3 py-12 text-center text-muted">
                                    {{ $stats['total'] === 0 ? __('library.empty') : __('library.no_results') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $games->links() }}

        <div class="flex flex-col gap-3 text-sm text-muted">
            <p class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <span class="font-mono text-xs tracking-widest uppercase">{{ __('library.legend.label') }}</span>
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
