@extends('pdf.layout')

@section('title', 'Plan de table')

@section('content')
    <p class="muted">{{ $rsvp['headcount'] }} couverts attendus. Les invités absents ne figurent pas dans ce plan.</p>

    @forelse ($tables as $table)
        <div class="table-block">
            <h2>{{ $table['table'] }} — {{ $table['headcount'] }} couvert(s)</h2>
            <table>
                <thead><tr><th>Invité</th><th>Réponse</th><th>Accompagnants</th><th>Régime</th></tr></thead>
                <tbody>
                @foreach ($table['guests'] as $guest)
                    <tr>
                        <td>{{ $guest->name }}</td>
                        <td>{{ $guest->rsvp_status->label() }}</td>
                        <td>{{ $guest->rsvp_status->value === 'accepted' ? $guest->companions_count : '—' }}</td>
                        <td>{{ $guest->rsvp_status->value === 'accepted' ? ($guest->dietary_restrictions ?: '—') : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="muted">Aucun invité à placer.</p>
    @endforelse
@endsection
