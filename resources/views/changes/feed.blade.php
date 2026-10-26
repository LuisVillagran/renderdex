@php
    use App\Enums\ChangeType;

    // Una entrada por juego y día, como en la página.
    $entries = $changes->groupBy(fn ($change) => $change->detected_at->toDateString().'-'.$change->game_id);
    $describe = fn ($change) => __('changes.type_label.'.$change->type->value).' '.trim(__('technologies.label.'.$change->technology->value).' '.match ($change->type) {
        ChangeType::Upgraded => $change->previous_version.' → '.$change->version,
        default => $change->version,
    });
@endphp
{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="es">
    <title>{{ __('changes.feed_title') }}</title>
    <subtitle>{{ __('changes.description') }}</subtitle>
    <id>{{ route('changes.index') }}</id>
    <link rel="self" href="{{ route('changes.feed') }}"/>
    <link rel="alternate" href="{{ route('changes.index') }}"/>
    <updated>{{ ($changes->first()?->detected_at ?? now())->toAtomString() }}</updated>
    <author><name>{{ config('app.name') }}</name></author>
    <rights>Datos de PCGamingWiki, CC BY-NC-SA 3.0</rights>
    @foreach ($entries as $key => $entryChanges)
        @php $game = $entryChanges->first()->game; @endphp
        <entry>
            <id>{{ route('changes.index') }}#{{ $key }}</id>
            <title>{{ __('changes.feed_entry', ['game' => $game->title, 'changes' => $entryChanges->map($describe)->implode(', ')]) }}</title>
            <link rel="alternate" href="{{ $game->url() }}"/>
            <updated>{{ $entryChanges->first()->detected_at->toAtomString() }}</updated>
        </entry>
    @endforeach
</feed>
