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

$base = 'relative inline-flex items-center border text-sm font-medium leading-5';
$link = $base.' border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100 focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800';
$disabled = $base.' cursor-default border-zinc-300 bg-white text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-600';
$current = $base.' z-10 cursor-default border-sky-600 bg-sky-600 text-white';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
            {{-- Móvil: solo anterior y siguiente --}}
            <div class="flex flex-1 justify-between sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="{{ $disabled }} rounded-lg px-4 py-2">{{ __('pagination.previous') }}</span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="{{ $link }} rounded-lg px-4 py-2">
                        {{ __('pagination.previous') }}
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="{{ $link }} rounded-lg px-4 py-2">
                        {{ __('pagination.next') }}
                    </button>
                @else
                    <span class="{{ $disabled }} rounded-lg px-4 py-2">{{ __('pagination.next') }}</span>
                @endif
            </div>

            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Showing') }}
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $paginator->firstItem() }}</span>
                    {{ __('to') }}
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $paginator->lastItem() }}</span>
                    {{ __('of') }}
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $paginator->total() }}</span>
                    {{ __('results') }}
                </p>

                <span class="relative z-0 inline-flex rounded-lg rtl:flex-row-reverse">
                    @if ($paginator->onFirstPage())
                        <span class="{{ $disabled }} rounded-l-lg px-2 py-2" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="{{ $link }} rounded-l-lg px-2 py-2" aria-label="{{ __('pagination.previous') }}">
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
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="{{ $link }} -ml-px rounded-r-lg px-2 py-2" aria-label="{{ __('pagination.next') }}">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </button>
                    @else
                        <span class="{{ $disabled }} -ml-px rounded-r-lg px-2 py-2" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    @endif
                </span>
            </div>
        </nav>
    @endif
</div>
