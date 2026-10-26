{{--
    Combobox ARIA: el foco queda en el input y las flechas mueven la opción activa
    (aria-activedescendant). Enter abre la opción activa o, sin ninguna, envía el formulario.
--}}
<form
    action="{{ route('catalog') }}"
    method="get"
    role="search"
    x-data="{
        open: false,
        active: -1,
        options() { return [...$el.querySelectorAll('[role=option]')] },
        refresh(value) { this.open = value.trim().length >= {{ \App\Livewire\GameSearchBox::MIN_LENGTH }}; this.active = -1 },
        move(step) {
            const options = this.options();
            if (! options.length) return;
            this.open = true;
            this.active = (this.active + step + options.length) % options.length;
            options[this.active].scrollIntoView({ block: 'nearest' });
        },
        choose(event) {
            const option = this.options()[this.active];
            if (this.open && option) {
                event.preventDefault();
                window.location.href = option.querySelector('a').href;
            }
        },
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape="open = false; active = -1"
    @if ($compact)
        x-on:keydown.window="if ($event.key === '/' && ! document.getElementById('library-search') && ! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { $event.preventDefault(); $refs.input.focus() }"
    @endif
    @class(['relative flex flex-col gap-2', 'w-full' => $compact])
>
    <label for="{{ $inputId }}" @class(['sr-only' => $compact, 'font-display text-lg font-semibold' => ! $compact])>{{ $label ?? __('search.label') }}</label>

    <div class="flex flex-col gap-2 sm:flex-row sm:gap-0">
        <div class="relative flex min-w-0 flex-1">
            <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.38 9.84l3.14 3.14a.75.75 0 1 0 1.06-1.06l-3.14-3.14A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" /></svg>
            <input
                id="{{ $inputId }}"
                x-ref="input"
                type="search"
                name="q"
                wire:model.live.debounce.250ms="query"
                x-on:input="refresh($event.target.value)"
                x-on:focus="refresh($event.target.value)"
                x-on:keydown.down.prevent="move(1)"
                x-on:keydown.up.prevent="move(-1)"
                x-on:keydown.enter="choose($event)"
                role="combobox"
                aria-autocomplete="list"
                aria-controls="{{ $inputId }}-listbox"
                x-bind:aria-expanded="open ? 'true' : 'false'"
                x-bind:aria-activedescendant="active >= 0 ? '{{ $inputId }}-option-' + active : null"
                @if ($compact) aria-keyshortcuts="/" @endif
                placeholder="{{ __('search.placeholder') }}"
                autocomplete="off"
                spellcheck="false"
                @class([
                    'w-full rounded border border-line-strong bg-canvas pl-9 placeholder:text-muted focus:border-accent focus:outline-2 focus:-outline-offset-1 focus:outline-accent',
                    'py-1.5 pr-8 text-xs' => $compact,
                    'py-2.5 pr-3 text-sm' => ! $compact,
                ])
            >
            @if ($compact)
                <kbd class="pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 border border-line-strong px-1.5 font-mono text-[0.65rem] text-muted" aria-hidden="true">/</kbd>
            @endif
        </div>

        @unless ($compact)
            <button type="submit" class="focus-ring rounded border border-accent px-5 py-2.5 text-sm font-semibold text-accent transition hover:bg-accent hover:text-on-accent active:translate-y-px sm:-ml-px">
                {{ __('search.submit') }} ↵
            </button>
        @endunless
    </div>

    <div
        x-show="open"
        x-cloak
        @class([
            'absolute top-full right-0 left-0 z-30 mt-1 flex max-h-[min(28rem,70vh)] flex-col overflow-y-auto border border-line-strong bg-panel',
            'sm:right-auto sm:w-[28rem]' => $compact,
        ])
    >
        <div wire:loading.delay class="loading-bar z-10" aria-hidden="true"></div>

        @if ($throttled)
            <p class="px-3 py-4 font-mono text-xs text-muted" role="status">{{ __('search.throttled') }}</p>
        @elseif ($searching && $suggestions->isEmpty())
            <p class="px-3 py-4 font-mono text-xs text-muted" role="status"><span class="text-accent" aria-hidden="true">∅ </span>{{ __('search.no_results', ['query' => trim($query)]) }}</p>
        @endif

        <ul id="{{ $inputId }}-listbox" role="listbox" aria-label="{{ __('search.suggestions') }}" class="divide-y divide-line">
            @foreach ($suggestions as $suggestion)
                <li
                    id="{{ $inputId }}-option-{{ $loop->index }}"
                    wire:key="{{ $inputId }}-{{ $suggestion['url'] }}"
                    role="option"
                    x-bind:aria-selected="active === {{ $loop->index }} ? 'true' : 'false'"
                    x-bind:class="active === {{ $loop->index }} && 'bg-raised shadow-[inset_2px_0_0_var(--rd-accent)]'"
                    x-on:mouseenter="active = {{ $loop->index }}"
                >
                    <a href="{{ $suggestion['url'] }}" tabindex="-1" class="flex items-center gap-3 px-3 py-2">
                        <x-game-cover :appid="$suggestion['appid']" :cover="$suggestion['cover']" :title="$suggestion['title']" size="xs" />
                        <span class="flex min-w-0 flex-col gap-0.5">
                            <span class="truncate text-sm font-medium"><x-highlight :text="$suggestion['title']" :query="$query" /></span>
                            <span class="truncate font-mono text-[0.7rem]">
                                @if ($suggestion['supported'] !== [])
                                    <span class="text-accent">{{ implode(' · ', $suggestion['supported']) }}</span>
                                @elseif ($suggestion['hasData'])
                                    <span class="text-muted">{{ __('search.none_supported') }}</span>
                                @else
                                    <span class="text-muted">{{ __('search.no_data') }}</span>
                                @endif
                            </span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($suggestions->isNotEmpty())
            <button type="submit" class="focus-ring border-t border-line px-3 py-2 text-left text-sm font-medium text-accent hover:bg-raised">
                {{ __('search.see_all', ['query' => trim($query)]) }} →
            </button>
        @endif
    </div>
</form>
