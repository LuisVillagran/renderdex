{{-- Tecnologías bajo el nombre, en pantallas chicas: no hay espacio para las columnas. --}}
@props(['game', 'name', 'gpuProfile', 'highlightedVendor'])

@php
    use App\Enums\Technology;
    use App\Enums\Vendor;

    $groups = Technology::byVendor();
    // Con una marca elegida, solo se muestran sus tecnologías y Ray Tracing.
    $compactGroups = $highlightedVendor ? array_intersect_key($groups, array_flip([$highlightedVendor->value, Vendor::All->value])) : $groups;
@endphp

<div {{ $attributes->class(['lg:hidden']) }}>
    @if (! $game?->has_data)
        <x-no-data :game="$game" :name="$name" />
    @elseif ($gpuProfile)
        <x-gpu-summary :game="$game" :gpu="$gpuProfile" />
    @else
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            @foreach ($compactGroups as $vendor => $groupTechnologies)
                <x-tech-pills wire:key="compact-{{ $vendor }}" :game="$game" :technologies="$groupTechnologies" />
            @endforeach
        </div>
    @endif
</div>
