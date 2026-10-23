@php
    use App\Enums\Technology;
    use App\Enums\Vendor;
    use Illuminate\Support\Number;

    $groups = Technology::byVendor();
    $isHighlighted = fn (string $vendor) => $highlightedVendor?->value === $vendor;
    // En pantallas chicas, con una marca elegida, solo se muestran sus tecnologías y Ray Tracing.
    $compactGroups = $highlightedVendor ? array_intersect_key($groups, array_flip([$highlightedVendor->value, Vendor::All->value])) : $groups;

    // Resalta lo buscado dentro del título; todo se escapa antes de armar el HTML.
    $needle = trim($search);
    $highlight = function (string $title) use ($needle): string {
        $position = $needle === '' ? false : mb_stripos($title, $needle);

        if ($position === false) {
            return e($title);
        }

        $length = mb_strlen($needle);

        return e(mb_substr($title, 0, $position))
            .'<mark class="bg-accent/20 text-fg">'.e(mb_substr($title, $position, $length)).'</mark>'
            .e(mb_substr($title, $position + $length));
    };
@endphp

<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex max-w-xl flex-col gap-3">
            <p class="font-mono text-xs text-muted uppercase"><span class="text-accent" aria-hidden="true">//</span> {{ __('catalog.eyebrow') }}</p>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ __('catalog.heading') }}</h1>
            <p class="text-muted">{{ __('catalog.intro') }}</p>
        </div>

        <x-gpu-selector :gpu="$gpu" :has-filters="$activeFilters !== []" />
    </div>

    <x-technology-filters :active-filters="$activeFilters" :search="$search" :placeholder="__('catalog.search.placeholder')" />

    <p class="flex flex-wrap items-baseline justify-between gap-2 font-mono text-xs text-muted" role="status">
        <span><span class="text-fg">{{ trans_choice('catalog.results', $games->total(), ['count' => Number::format($games->total())]) }}</span></span>
        @if ($needle === '')
            <span>{{ __('catalog.top_hint') }}</span>
        @endif
    </p>

    <div class="frame overflow-hidden" wire:loading.class="opacity-60">
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
                        <tr wire:key="catalog-{{ $game->id }}" class="transition-colors hover:bg-raised/60">
                            <td class="hidden py-3 pr-1 pl-4 align-top sm:table-cell lg:align-middle">
                                <x-game-cover :appid="$appid" />
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-col gap-2">
                                    <div class="flex flex-col gap-0.5">
                                        <a href="https://www.pcgamingwiki.com/w/index.php?curid={{ $game->pcgw_page_id }}" rel="noopener" title="{{ __('library.wiki_link', ['name' => $game->title]) }}" class="focus-ring self-start font-medium decoration-accent underline-offset-4 hover:text-accent hover:underline">
                                            {!! $highlight($game->title) !!}
                                        </a>
                                        @if ($appid)
                                            <a href="https://store.steampowered.com/app/{{ $appid }}/" rel="noopener" aria-label="{{ __('catalog.store_link_label', ['name' => $game->title]) }}" class="focus-ring self-start font-mono text-[0.7rem] text-muted uppercase hover:text-accent">
                                                {{ __('catalog.store_link') }} ↗
                                            </a>
                                        @endif
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
