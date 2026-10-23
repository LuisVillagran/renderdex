@props(['game', 'technologies'])

@inject('classifier', \App\Services\FeatureClassifier::class)

<div {{ $attributes->class(['flex flex-wrap gap-1']) }}>
    @foreach ($technologies as $technology)
        <x-tech-pill
            :technology="$technology"
            :support="$game?->{$technology->value} ?? \App\Enums\Support::Unknown"
            :version="$classifier->latestVersion($game?->upscaling_tech, $technology)"
        />
    @endforeach
</div>
