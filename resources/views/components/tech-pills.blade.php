{{-- Con $delay (ms), las píldoras se encienden una a una a partir de ese momento. --}}
@props(['game', 'technologies', 'delay' => null])

@inject('classifier', \App\Services\FeatureClassifier::class)

<div {{ $attributes->class(['flex flex-wrap gap-1']) }}>
    @foreach ($technologies as $technology)
        @if ($delay === null)
            <x-tech-pill
                :technology="$technology"
                :support="$game?->{$technology->value} ?? \App\Enums\Support::Unknown"
                :version="$classifier->latestVersion($game?->upscaling_tech, $technology)"
            />
        @else
            <x-tech-pill
                :technology="$technology"
                :support="$game?->{$technology->value} ?? \App\Enums\Support::Unknown"
                :version="$classifier->latestVersion($game?->upscaling_tech, $technology)"
                class="animate-ignite"
                style="--delay: {{ $delay + $loop->index * 45 }}ms"
            />
        @endif
    @endforeach
</div>
