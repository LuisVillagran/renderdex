@php
    use App\Enums\Capability;

    // Agrupado por día y, dentro de cada día, por juego.
    $days = $changes->getCollection()
        ->groupBy(fn ($change) => $change->detected_at->toDateString())
        ->map(fn ($dayChanges) => $dayChanges->groupBy('game_id'));
@endphp

<x-layout :title="__('changes.title')" :description="__('changes.description')">
    <x-slot:head>
        <link rel="alternate" type="application/atom+xml" title="{{ __('changes.feed_title') }}" href="{{ route('changes.feed') }}">
    </x-slot:head>

    <div class="flex flex-col gap-10">
        <header class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex max-w-2xl flex-col gap-3">
                <p class="text-sm font-semibold text-accent"><x-decode :text="__('changes.eyebrow')" /></p>
                <h1 class="font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ __('changes.heading') }}</h1>
                <p class="text-muted">{{ __('changes.intro') }}</p>
            </div>
            <a href="{{ route('changes.feed') }}" class="focus-ring rounded self-start border border-line-strong px-3 py-1.5 text-sm text-muted hover:border-accent hover:text-accent lg:self-end">
                {{ __('changes.feed') }} ↗
            </a>
        </header>

        <nav aria-label="{{ __('changes.filter_label') }}" class="flex flex-wrap gap-1.5 border-y border-line py-4 font-mono text-xs">
            @foreach ([null, ...Capability::cases()] as $option)
                @php $isActive = $capability === $option; @endphp
                <a
                    href="{{ route('changes.index', $option ? ['cap' => $option->value] : []) }}"
                    @if ($isActive) aria-current="page" @endif
                    @class([
                        'focus-ring rounded border px-2.5 py-1 transition',
                        'border-accent bg-accent/12 text-accent' => $isActive,
                        'border-line-strong text-muted hover:border-fg hover:text-fg' => ! $isActive,
                    ])
                >
                    {{ $option ? __('library.capability.'.$option->value.'.label') : __('changes.all') }}
                </a>
            @endforeach
        </nav>

        @forelse ($days as $day => $games)
            <section aria-labelledby="day-{{ $day }}" class="flex flex-col gap-3">
                <h2 id="day-{{ $day }}" class="label">{{ __('changes.detected_on', ['date' => \Illuminate\Support\Carbon::parse($day)->isoFormat('LL')]) }}</h2>
                <ul class="frame divide-y divide-line">
                    @foreach ($games as $gameChanges)
                        @php $game = $gameChanges->first()->game; @endphp
                        <li class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" data-reveal>
                            <a href="{{ $game->url() }}" class="focus-ring font-medium decoration-accent underline-offset-4 hover:text-accent hover:underline">{{ $game->title }}</a>
                            <span class="flex flex-wrap gap-1">
                                @foreach ($gameChanges as $change)
                                    <x-change-label :change="$change" />
                                @endforeach
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="py-14 text-center font-mono text-sm text-muted"><span class="text-accent" aria-hidden="true">∅ </span>{{ __('changes.empty') }}</p>
        @endforelse

        {{ $changes->links() }}

        <p class="border-t border-line pt-5 text-sm text-muted">
            {!! __('layout.footer.data_credit', [
                'pcgw' => '<a href="https://www.pcgamingwiki.com/" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener">PCGamingWiki</a>',
                'license' => '<a href="https://creativecommons.org/licenses/by-nc-sa/3.0/" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener license">CC BY-NC-SA 3.0</a>',
            ]) !!}
        </p>
    </div>
</x-layout>
