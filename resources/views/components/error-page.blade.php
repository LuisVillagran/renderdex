@props(['code'])

<x-layout :title="__('errors.'.$code.'.title')">
    <section class="flex flex-col items-start gap-4 py-12">
        <p class="font-mono text-sm text-accent">ERR_{{ $code }}</p>
        <h1 class="font-display text-3xl font-semibold">{{ __('errors.'.$code.'.title') }}</h1>
        <p class="max-w-xl text-muted">{{ __('errors.'.$code.'.body') }}</p>
        <a href="{{ route('home') }}" class="focus-ring font-mono text-sm text-accent underline underline-offset-4">{{ __('errors.back_home') }}</a>
    </section>
</x-layout>
