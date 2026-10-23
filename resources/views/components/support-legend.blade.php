@php
    use App\Enums\Support;
    use App\Enums\Technology;
@endphp

<p {{ $attributes->class(['flex flex-wrap items-center gap-x-5 gap-y-2']) }}>
    <span class="label">{{ __('library.legend.label') }}</span>
    @foreach ([Support::Yes, Support::No, Support::Unknown] as $support)
        <span class="inline-flex items-center gap-1.5">
            {{-- El texto visible ya lo dice: la píldora se oculta a lectores de pantalla. --}}
            <x-tech-pill :technology="Technology::Dlss" :support="$support" aria-hidden="true" />
            {{ __('technologies.support.'.$support->value) }}
        </span>
    @endforeach
    <span>{{ __('library.legend.unknown') }}</span>
</p>
