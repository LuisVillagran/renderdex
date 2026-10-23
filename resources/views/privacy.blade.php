<x-layout :title="__('privacy.title')">
    <div class="grid gap-12 lg:grid-cols-[14rem_1fr] lg:gap-16">
        <aside class="lg:sticky lg:top-28 lg:self-start">
            <nav aria-labelledby="privacy-contents" class="flex flex-col gap-3">
                <h2 id="privacy-contents" class="label">{{ __('privacy.contents') }}</h2>
                <ol class="flex flex-col gap-1.5 border-l border-line font-mono text-xs">
                    @foreach (__('privacy.sections') as $section)
                        <li>
                            <a href="#{{ $section['id'] }}" class="focus-ring -ml-px flex gap-2 border-l border-transparent py-0.5 pl-3 text-muted hover:border-accent hover:text-fg">
                                <span class="text-accent" aria-hidden="true">§{{ $loop->iteration }}</span>
                                {{ $section['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </aside>

        <article class="flex max-w-2xl flex-col gap-10">
            <header class="flex flex-col gap-4">
                <p class="font-mono text-xs text-muted uppercase"><span class="text-accent" aria-hidden="true">//</span> <x-decode :text="__('privacy.title')" /></p>
                <h1 class="font-display text-4xl font-semibold tracking-tight">{{ __('privacy.heading') }}</h1>
                <p class="text-lg text-muted">{{ __('privacy.intro') }}</p>
                <p class="font-mono text-xs text-muted">{{ __('privacy.updated') }}</p>
            </header>

            @foreach (__('privacy.sections') as $section)
                <section id="{{ $section['id'] }}" class="flex scroll-mt-28 flex-col gap-4 border-t border-line pt-6" aria-labelledby="{{ $section['id'] }}-title">
                    <h2 id="{{ $section['id'] }}-title" class="flex items-baseline gap-3 font-display text-2xl font-semibold">
                        <span class="font-mono text-sm text-accent" aria-hidden="true">§{{ $loop->iteration }}</span>
                        {{ $section['title'] }}
                    </h2>

                    @isset($section['items'])
                        <ul class="flex flex-col gap-3">
                            @foreach ($section['items'] as $item)
                                <li class="flex gap-3 text-muted">
                                    <span class="font-mono text-accent" aria-hidden="true">–</span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">
                            {!! __('privacy.sections.'.$loop->index.'.body', [
                                'repository' => '<a href="'.e(config('app.repository_url')).'" class="text-fg underline decoration-line-strong underline-offset-4 hover:decoration-accent" rel="noopener">'.e($section['repository']).'</a>',
                            ]) !!}
                        </p>
                    @endisset
                </section>
            @endforeach
        </article>
    </div>
</x-layout>
