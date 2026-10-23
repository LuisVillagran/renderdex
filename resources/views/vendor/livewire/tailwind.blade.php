{{-- Publicada desde Livewire y adaptada a la paleta del sitio, con la página actual destacada. --}}
@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$base = 'relative inline-flex items-center border font-mono text-xs font-medium leading-5';
$link = $base.' focus-ring border-line-strong bg-panel text-fg hover:border-accent hover:text-accent focus-visible:z-10';
$disabled = $base.' cursor-default border-line-strong bg-panel text-muted/50';
$current = $base.' z-10 cursor-default border-accent bg-accent text-on-accent font-bold';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
            {{-- Móvil: solo anterior y siguiente --}}
            <div class="flex flex-1 justify-between sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="{{ $disabled }} px-4 py-2">{{ __('pagination.previous') }}</span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="{{ $link }} px-4 py-2">
                        {{ __('pagination.previous') }}
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="{{ $link }} px-4 py-2">
                        {{ __('pagination.next') }}
                    </button>
                @else
                    <span class="{{ $disabled }} px-4 py-2">{{ __('pagination.next') }}</span>
                @endif
            </div>

            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <p class="font-mono text-xs text-muted">
                    {{ __('Showing') }}
                    <span class="font-mono font-medium text-fg">{{ $paginator->firstItem() }}</span>
                    {{ __('to') }}
                    <span class="font-mono font-medium text-fg">{{ $paginator->lastItem() }}</span>
                    {{ __('of') }}
                    <span class="font-mono font-medium text-fg">{{ $paginator->total() }}</span>
                    {{ __('results') }}
                </p>

                <span class="relative z-0 inline-flex rtl:flex-row-reverse">
                    @if ($paginator->onFirstPage())
                        <span class="{{ $disabled }} px-2 py-2" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="{{ $link }} px-2 py-2" aria-label="{{ __('pagination.previous') }}">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </button>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="{{ $disabled }} -ml-px px-4 py-2" aria-disabled="true">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}" class="-ml-px">
                                    @if ($page == $paginator->currentPage())
                                        <span class="{{ $current }} px-4 py-2" aria-current="page">{{ $page }}</span>
                                    @else
                                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="{{ $link }} px-4 py-2" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                            {{ $page }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="{{ $link }} -ml-px px-2 py-2" aria-label="{{ __('pagination.next') }}">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </button>
                    @else
                        <span class="{{ $disabled }} -ml-px px-2 py-2" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    @endif
                </span>
            </div>
        </nav>
    @endif
</div>
