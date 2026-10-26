{{-- Un cambio de la wiki: "+ DLSS 4.5", "− XeSS" o "↑ FSR 3.1 → 4". --}}
@props(['change'])

@php
    use App\Enums\ChangeType;

    $technology = __('technologies.label.'.$change->technology->value);
    $text = match ($change->type) {
        ChangeType::Upgraded => __('changes.type.upgraded', ['technology' => $technology, 'from' => $change->previous_version, 'to' => $change->version]),
        default => trim($technology.' '.$change->version),
    };
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1 border px-1.5 font-mono text-[0.7rem] leading-[1.125rem] font-medium whitespace-nowrap',
    'border-accent/60 bg-accent/12 text-accent' => $change->type !== ChangeType::Removed,
    'border-line-strong text-muted line-through decoration-muted/70' => $change->type === ChangeType::Removed,
]) }}>
    <span aria-hidden="true">{{ match ($change->type) { ChangeType::Added => '+', ChangeType::Removed => '−', ChangeType::Upgraded => '↑' } }}</span>
    <span class="sr-only">{{ __('changes.type_label.'.$change->type->value) }}:</span>
    {{ $text }}
</span>
