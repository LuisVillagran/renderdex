@props(['code'])

<x-layout :title="__('errors.'.$code.'.title')">
    <section class="flex flex-col items-start gap-4 py-12">
        <p class="font-mono text-sm text-accent" aria-hidden="true">ERR_{{ $code }}</p>
        <h1 class="font-display text-4xl font-semibold tracking-tight">{{ __('errors.'.$code.'.title') }}</h1>
        <p class="max-w-xl text-muted">{{ __('errors.'.$code.'.body') }}</p>
        <a href="{{ route('home') }}" class="focus-ring rounded border border-accent px-4 py-2 text-sm font-semibold text-accent hover:bg-accent hover:text-on-accent">← {{ __('errors.back_home') }}</a>
    </section>
</x-layout>
