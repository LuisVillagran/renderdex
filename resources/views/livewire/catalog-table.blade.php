@php
    use App\Enums\Technology;
    use Illuminate\Support\Number;

    $needle = trim($search);
@endphp

<div class="flex flex-col gap-8">
    <div class="flex max-w-2xl flex-col gap-3">
        <p class="text-sm font-semibold text-accent"><x-decode :text="__('catalog.eyebrow')" /></p>
        <h1 class="font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ __('catalog.heading') }}</h1>
        <p class="text-muted">{{ __('catalog.intro') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-8">
    <x-technology-filters :filter="$filter" :has-active-filters="$hasActiveFilters" :gpu="$gpu" :gpu-model="$gpuModel" />

    {{-- `all` muestra las columnas por marca además de "En tu GPU"; se recuerda en el navegador. --}}
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
        <x-name-search :placeholder="__('catalog.search.placeholder')" />

        <div class="flex flex-wrap items-baseline justify-between gap-2 font-mono text-xs text-muted">
            <p role="status">
                <span class="text-fg">{{ trans_choice('catalog.results', $games->total(), ['count' => Number::format($games->total())]) }}</span>
                @if ($approximate && $games->total() > 0)
                    <span class="ml-2 text-warn">≈ {{ __('catalog.approximate', ['query' => $needle]) }}</span>
                @elseif ($needle === '')
                    <span class="ml-2">{{ __('catalog.top_hint') }}</span>
                @endif
            </p>
            <div class="flex items-center gap-4">
                @if ($gpuProfile && $layout === 'table')
                    <button type="button" x-on:click="toggleAll()" x-bind:aria-pressed="all ? 'true' : 'false'" class="focus-ring hidden hover:text-accent lg:inline-flex">
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
                    <p class="frame px-3 py-14 text-center font-mono text-sm text-muted">
                        <span class="text-accent" aria-hidden="true">∅ </span>{{ __('catalog.no_results') }}
                    </p>
                @else
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-7 sm:grid-cols-3 md:grid-cols-4 lg:gap-x-5">
                        @foreach ($games as $game)
                            @php $appid = $game->steamAppids->first()?->steam_appid; @endphp
                            <li wire:key="catalog-card-{{ $game->id }}" class="animate-rise" style="--delay: {{ $loop->index * 25 }}ms">
                                <x-game-card :game="$game" :appid="$appid" :name="$game->title" :gpu-profile="$gpuProfile" :query="$needle">
                                    <x-slot:meta>
                                        <a href="{{ $game->wikiUrl() }}" rel="noopener" aria-label="{{ __('library.wiki_link', ['name' => $game->title]) }}" class="focus-ring hover:text-accent">{{ __('catalog.wiki_link') }} ↗</a>
                                        @if ($appid)
                                            <a href="https://store.steampowered.com/app/{{ $appid }}/" rel="noopener" aria-label="{{ __('catalog.store_link_label', ['name' => $game->title]) }}" class="focus-ring hover:text-accent">{{ __('catalog.store_link') }} ↗</a>
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
                    <caption class="sr-only">{{ __('catalog.title') }}</caption>
                    <thead class="border-b border-line-strong text-left">
                        <tr>
                            <th scope="col" class="py-3 pr-1 pl-3 sm:pl-4"><span class="sr-only">{{ __('library.table.cover') }}</span></th>
                            <th scope="col" class="label px-3 py-3 font-normal">{{ __('library.table.name') }}</th>
                            <x-technology-headers :gpu-profile="$gpuProfile" :highlighted-vendor="$highlightedVendor" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($games as $game)
                            @php $appid = $game->steamAppids->first()?->steam_appid; @endphp
                            <tr wire:key="catalog-{{ $game->id }}" class="animate-rise transition-colors hover:bg-raised/60 hover:shadow-[inset_2px_0_0_var(--rd-accent)]" style="--delay: {{ $loop->index * 18 }}ms">
                                <td class="py-3 pr-1 pl-3 align-top sm:pl-4">
                                    {{-- El título ya enlaza a la ficha: la portada es un atajo para el mouse. --}}
                                    <a href="{{ $game->url() }}" tabindex="-1" aria-hidden="true" class="block transition hover:brightness-110">
                                        <x-game-cover :appid="$appid" :cover="$game->cover_url" :title="$game->title" />
                                    </a>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex flex-col gap-0.5">
                                            <a href="{{ $game->url() }}" class="focus-ring self-start font-medium decoration-accent underline-offset-4 hover:text-accent hover:underline">
                                                <x-highlight :text="$game->title" :query="$needle" />
                                            </a>
                                            <span class="flex flex-wrap gap-x-3 text-xs text-muted">
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

                                        <x-technology-compact :game="$game" :name="$game->title" :gpu-profile="$gpuProfile" :highlighted-vendor="$highlightedVendor" />
                                    </div>
                                </td>
                                <x-technology-cells :game="$game" :row-key="$game->id" :name="$game->title" :gpu-profile="$gpuProfile" :highlighted-vendor="$highlightedVendor" />
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 2 + ($gpuProfile ? 1 : 0) + count(Technology::byVendor()) }}" class="px-3 py-14 text-center font-mono text-sm text-muted">
                                    <span class="text-accent" aria-hidden="true">∅ </span>{{ __('catalog.no_results') }}
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

    <x-support-legend class="border-t border-line pt-5 text-sm text-muted" />
</div>
