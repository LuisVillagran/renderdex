@php
    use App\Enums\Technology;
    use App\Enums\Vendor;
    use Illuminate\Support\Number;

    $groups = Technology::byVendor();
    $isHighlighted = fn (string $vendor) => $highlightedVendor?->value === $vendor;
    // En pantallas chicas, con una marca elegida, solo se muestran sus tecnologías y Ray Tracing.
    $compactGroups = $highlightedVendor ? array_intersect_key($groups, array_flip([$highlightedVendor->value, Vendor::All->value])) : $groups;

    // Resalta cada palabra buscada dentro del título; todo se escapa antes de armar el HTML.
    $needle = trim($search);
    $words = preg_split('/\s+/u', $needle, flags: PREG_SPLIT_NO_EMPTY) ?: [];
    $pattern = $words === [] ? null : '/('.implode('|', array_map(fn (string $word) => preg_quote($word, '/'), $words)).')/iu';
    $highlight = function (string $title) use ($pattern): string {
        if ($pattern === null) {
            return e($title);
        }

        return collect(preg_split($pattern, $title, flags: PREG_SPLIT_DELIM_CAPTURE) ?: [$title])
            ->map(fn (string $part, int $index) => $index % 2 === 1 ? '<mark class="bg-accent/20 text-fg">'.e($part).'</mark>' : e($part))
            ->implode('');
    };
@endphp

<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex max-w-xl flex-col gap-3">
            <p class="font-mono text-xs text-muted uppercase"><span class="text-accent" aria-hidden="true">//</span> <x-decode :text="__('catalog.eyebrow')" /></p>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ __('catalog.heading') }}</h1>
            <p class="text-muted">{{ __('catalog.intro') }}</p>
        </div>

        <x-gpu-selector :gpu="$gpu" :has-filters="$activeFilters !== []" />
    </div>

    <x-technology-filters :active-filters="$activeFilters" :search="$search" :placeholder="__('catalog.search.placeholder')" />

    <p class="flex flex-wrap items-baseline justify-between gap-2 font-mono text-xs text-muted" role="status">
        <span><span class="text-fg">{{ trans_choice('catalog.results', $games->total(), ['count' => Number::format($games->total())]) }}</span></span>
        @if ($approximate && $games->total() > 0)
            <span class="text-warn">≈ {{ __('catalog.approximate', ['query' => $needle]) }}</span>
        @elseif ($needle === '')
            <span>{{ __('catalog.top_hint') }}</span>
        @endif
    </p>

    <div class="frame relative overflow-hidden" wire:loading.class="opacity-70">
        <div wire:loading.delay class="loading-bar z-10" aria-hidden="true"></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="sr-only">{{ __('catalog.title') }}</caption>
                <thead class="border-b border-line-strong text-left">
                    <tr>
                        <th scope="col" class="hidden py-3 pr-1 pl-4 sm:table-cell"><span class="sr-only">{{ __('library.table.cover') }}</span></th>
                        <th scope="col" class="label px-3 py-3 font-normal">{{ __('library.table.name') }}</th>
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
                    @forelse ($games as $game)
                        @php $appid = $game->steamAppids->first()?->steam_appid; @endphp
                        <tr wire:key="catalog-{{ $game->id }}" class="animate-rise transition-colors hover:bg-raised/60 hover:shadow-[inset_2px_0_0_var(--rd-accent)]" style="--delay: {{ $loop->index * 18 }}ms">
                            <td class="hidden py-3 pr-1 pl-4 align-top sm:table-cell lg:align-middle">
                                <x-game-cover :appid="$appid" />
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-col gap-2">
                                    <div class="flex flex-col gap-0.5">
                                        <a href="{{ $game->url() }}" class="focus-ring self-start font-medium decoration-accent underline-offset-4 hover:text-accent hover:underline">
                                            {!! $highlight($game->title) !!}
                                        </a>
                                        <span class="flex flex-wrap gap-x-3 font-mono text-[0.7rem] text-muted uppercase">
                                            <a href="{{ $game->wikiUrl() }}" rel="noopener" aria-label="{{ __('library.wiki_link', ['name' => $game->title]) }}" class="focus-ring hover:text-accent">
                                                {{ __('catalog.wiki_link') }} ↗
                                            </a>
                                            @if ($appid)
                                                <a href="https://store.steampowered.com/app/{{ $appid }}/" rel="noopener" aria-label="{{ __('catalog.store_link_label', ['name' => $game->title]) }}" class="focus-ring hover:text-accent">
                                                    {{ __('catalog.store_link') }} ↗
                                                </a>
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Sin espacio para las columnas por marca: las píldoras van bajo el nombre. --}}
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 lg:hidden">
                                        @foreach ($compactGroups as $vendor => $groupTechnologies)
                                            <x-tech-pills wire:key="compact-{{ $game->id }}-{{ $vendor }}" :game="$game" :technologies="$groupTechnologies" />
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                            @foreach ($groups as $vendor => $groupTechnologies)
                                <td wire:key="cell-{{ $game->id }}-{{ $vendor }}" @class([
                                    'hidden px-3 py-3 lg:table-cell',
                                    'bg-accent/5' => $isHighlighted($vendor),
                                ])>
                                    <x-tech-pills :game="$game" :technologies="$groupTechnologies" />
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + count($groups) }}" class="px-3 py-14 text-center font-mono text-sm text-muted">
                                <span class="text-accent" aria-hidden="true">∅ </span>{{ __('catalog.no_results') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $games->links() }}

    <x-support-legend class="border-t border-line pt-5 text-sm text-muted" />
</div>
