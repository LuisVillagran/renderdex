@php
    use App\Enums\Support;
    use App\Enums\Technology;
    use App\Services\Library\LibraryBrowser;
    use Illuminate\Support\Number;

    $technologies = Technology::cases();
    $isHighlighted = fn (Technology $technology) => $highlightedVendor !== null && $technology->vendor() === $highlightedVendor;
    $sortIndicator = fn (string $column) => $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '';
    $ariaSort = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none';
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
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">{{ __('library.heading') }}</h1>

        <button
            type="button"
            x-data="{ copied: false }"
            x-on:click="navigator.clipboard.writeText(@js(route('library.show', $steamId))); copied = true; setTimeout(() => copied = false, 2000)"
            class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm hover:bg-zinc-100 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none dark:border-zinc-700 dark:hover:bg-zinc-800"
        >
            <span x-show="! copied">{{ __('library.share') }}</span>
            <span x-show="copied" x-cloak role="status">{{ __('library.copied') }}</span>
        </button>
    </div>

    @if ($isDemo)
        <p class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200" role="note">
            {{ __('library.demo') }}
        </p>
    @endif

    @if ($error === 'private')
        <section class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900" role="alert">
            <h2 class="text-lg font-semibold">{{ __('library.errors.private.title') }}</h2>
            <p class="text-zinc-600 dark:text-zinc-400">{{ __('library.errors.private.body') }}</p>
            <p class="text-zinc-600 dark:text-zinc-400">{{ __('home.privacy_help.steps') }}</p>
        </section>
    @elseif ($error === 'unavailable')
        <section class="flex flex-col items-start gap-3 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900" role="alert">
            <h2 class="text-lg font-semibold">{{ __('library.errors.unavailable.title') }}</h2>
            <p class="text-zinc-600 dark:text-zinc-400">{{ __('library.errors.unavailable.body') }}</p>
            <button type="button" wire:click="$refresh" class="rounded-lg bg-sky-600 px-4 py-2 font-medium text-white hover:bg-sky-700 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:outline-none dark:focus-visible:ring-offset-zinc-900">
                {{ __('library.errors.unavailable.retry') }}
            </button>
        </section>
    @else
        {{-- Estadísticas --}}
        <section aria-label="{{ __('library.stats.label') }}">
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-5 lg:grid-cols-10">
                <div class="col-span-1 rounded-lg border border-zinc-200 bg-white p-3 sm:col-span-1 lg:col-span-1 dark:border-zinc-800 dark:bg-zinc-900">
                    <dt class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('library.stats.total') }}</dt>
                    <dd class="text-xl font-semibold tabular-nums">{{ Number::format($stats['total']) }}</dd>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
                    <dt class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('library.stats.with_wiki_page') }}</dt>
                    <dd class="text-xl font-semibold tabular-nums">{{ Number::format($stats['with_wiki_page']) }}</dd>
                </div>
                @foreach ($technologies as $technology)
                    <div wire:key="stat-{{ $technology->value }}" @class([
                        'rounded-lg border bg-white p-3 dark:bg-zinc-900',
                        'border-sky-400 dark:border-sky-700' => $isHighlighted($technology),
                        'border-zinc-200 dark:border-zinc-800' => ! $isHighlighted($technology),
                    ])>
                        <dt class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('technologies.label.'.$technology->value) }}</dt>
                        <dd class="text-xl font-semibold tabular-nums">{{ Number::format($stats['technologies'][$technology->value]) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Controles --}}
        <section class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <fieldset class="flex flex-col gap-1.5">
                    <legend class="mb-1.5 text-sm font-medium">{{ __('library.gpu.label') }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['nvidia', 'amd', 'intel', 'all'] as $option)
                            <button
                                type="button"
                                wire:key="gpu-{{ $option }}"
                                x-on:click="chooseGpu('{{ $option }}')"
                                aria-pressed="{{ $gpu === $option ? 'true' : 'false' }}"
                                @class([
                                    'rounded-lg border px-3 py-1.5 text-sm font-medium focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none',
                                    'border-sky-600 bg-sky-600 text-white' => $gpu === $option,
                                    'border-zinc-300 hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800' => $gpu !== $option,
                                ])
                            >
                                {{ __('technologies.vendor.'.$option) }}
                            </button>
                        @endforeach
                    </div>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('library.gpu.help') }}</p>
                </fieldset>

                <div class="flex flex-col gap-1.5 lg:w-72">
                    <label for="library-search" class="text-sm font-medium">{{ __('library.search.label') }}</label>
                    <input
                        id="library-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('library.search.placeholder') }}"
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 placeholder:text-zinc-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/40 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:placeholder:text-zinc-500"
                    >
                </div>
            </div>

            <fieldset class="flex flex-col gap-1.5">
                <legend class="mb-1.5 text-sm font-medium">{{ __('library.filters.label') }}</legend>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($technologies as $technology)
                        <button
                            type="button"
                            wire:key="filter-{{ $technology->value }}"
                            wire:click="toggleFilter('{{ $technology->value }}')"
                            aria-pressed="{{ in_array($technology->value, $activeFilters, true) ? 'true' : 'false' }}"
                            @class([
                                'rounded-full border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none',
                                'border-emerald-600 bg-emerald-600 text-white' => in_array($technology->value, $activeFilters, true),
                                'border-zinc-300 hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800' => ! in_array($technology->value, $activeFilters, true),
                            ])
                        >
                            {{ __('technologies.label.'.$technology->value) }}
                        </button>
                    @endforeach

                    @if ($activeFilters !== [] || $search !== '')
                        <button type="button" wire:click="clearFilters" class="rounded-full px-3 py-1 text-sm text-sky-700 underline hover:text-sky-900 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none dark:text-sky-400 dark:hover:text-sky-300">
                            {{ __('library.filters.clear') }}
                        </button>
                    @endif
                </div>
            </fieldset>
        </section>

        {{-- Tabla --}}
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" wire:loading.class="opacity-60">
            <table class="w-full text-sm">
                <caption class="sr-only">{{ __('library.table.caption') }}</caption>
                <thead class="bg-zinc-50 text-left text-xs text-zinc-600 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="hidden px-3 py-2 sm:table-cell"><span class="sr-only">{{ __('library.table.cover') }}</span></th>
                        @foreach (['name' => __('library.table.name'), 'playtime' => __('library.table.playtime')] as $column => $label)
                            <th scope="col" aria-sort="{{ $ariaSort($column) }}" @class(['px-3 py-2', 'text-right' => $column === 'playtime'])>
                                <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 rounded font-semibold hover:text-zinc-900 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none dark:hover:text-zinc-100" aria-label="{{ __('library.table.sort_by', ['column' => $label]) }}">
                                    {{ $label }} <span aria-hidden="true">{{ $sortIndicator($column) }}</span>
                                </button>
                            </th>
                        @endforeach
                        @foreach ($technologies as $technology)
                            <th scope="col" wire:key="th-{{ $technology->value }}" title="{{ __('technologies.description.'.$technology->value) }}" @class([
                                'px-2 py-2 text-center font-semibold',
                                'hidden md:table-cell' => $technology->isFrameGeneration(),
                                'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200' => $isHighlighted($technology),
                            ])>
                                <span class="block text-[0.65rem] font-normal tracking-wide uppercase" aria-hidden="true">{{ __('technologies.vendor.'.$technology->vendor()->value) }}</span>
                                <abbr title="{{ __('technologies.description.'.$technology->value) }}" class="no-underline">{{ __('technologies.column.'.$technology->value) }}</abbr>
                            </th>
                        @endforeach
                        <th scope="col" class="hidden px-3 py-2 font-semibold lg:table-cell">{{ __('library.table.versions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($games as $entry)
                        <tr wire:key="game-{{ $entry->owned->appid }}">
                            <td class="hidden px-3 py-2 sm:table-cell">
                                <img
                                    src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{{ $entry->owned->appid }}/capsule_184x69.jpg"
                                    alt=""
                                    width="92"
                                    height="35"
                                    loading="lazy"
                                    onerror="this.onerror = null; this.src = '{{ asset('images/cover-placeholder.svg') }}'"
                                    class="h-[35px] w-[92px] rounded bg-zinc-200 object-cover dark:bg-zinc-800"
                                >
                            </td>
                            <td class="px-3 py-2">
                                @if ($entry->game)
                                    <a href="https://www.pcgamingwiki.com/w/index.php?curid={{ $entry->game->pcgw_page_id }}" rel="noopener" title="{{ __('library.wiki_link', ['name' => $entry->owned->name]) }}" class="font-medium hover:underline focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none">
                                        {{ $entry->owned->name }}
                                    </a>
                                    @if ($entry->matchedBy === \App\Enums\MatchSource::Title)
                                        <span class="block text-xs text-amber-700 dark:text-amber-400" title="{{ __('library.matched_by_title_help') }}">{{ __('library.matched_by_title') }}</span>
                                    @endif
                                @else
                                    <span class="font-medium">{{ $entry->owned->name }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ __('library.no_wiki_page') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                {{ Number::format($entry->owned->playtimeMinutes / 60, maxPrecision: 1) }}
                            </td>
                            @foreach ($technologies as $technology)
                                <td wire:key="cell-{{ $entry->owned->appid }}-{{ $technology->value }}" @class([
                                    'px-2 py-2 text-center',
                                    'hidden md:table-cell' => $technology->isFrameGeneration(),
                                    'bg-sky-50 dark:bg-sky-950/40' => $isHighlighted($technology),
                                ])>
                                    <x-support :value="$entry->game?->{$technology->value} ?? Support::Unknown" />
                                </td>
                            @endforeach
                            <td class="hidden px-3 py-2 text-xs text-zinc-600 lg:table-cell dark:text-zinc-400">
                                {{ collect([$entry->game?->upscaling_tech, $entry->game?->frame_gen_tech])->filter()->map(fn ($list) => str_replace(',', ', ', $list))->implode(' · ') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($technologies) + 4 }}" class="px-3 py-8 text-center text-zinc-600 dark:text-zinc-400">
                                {{ $stats['total'] === 0 ? __('library.empty') : __('library.no_results') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $games->links() }}

        <div class="flex flex-col gap-1 text-sm text-zinc-600 dark:text-zinc-400">
            <p class="flex flex-wrap items-center gap-x-1">
                <x-support :value="Support::Yes" /> <x-support :value="Support::No" /> <x-support :value="Support::Unknown" />
                {{ __('library.legend') }}
            </p>
            <p>{{ __('home.privacy_help.family_sharing') }}</p>
        </div>
    @endif
</div>
