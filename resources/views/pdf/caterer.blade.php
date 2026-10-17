@extends('pdf.layout')

@section('title', 'Fiche traiteur')

@section('content')
    <table class="kpi">
        <tr>
            <td>{{ $rsvp['headcount'] }}<small>couverts attendus</small></td>
            <td>{{ $rsvp['accepted'] }}<small>invités présents</small></td>
            <td>{{ $rsvp['pending'] }}<small>réponses en attente</small></td>
            <td>{{ $rsvp['declined'] }}<small>absents</small></td>
        </tr>
    </table>
    <p class="muted">Couverts = invités présents + leurs accompagnants. Les réponses en attente ne sont pas comptées.</p>

    <h2>Régimes alimentaires et allergies ({{ $dietary->count() }} invité(s), {{ $dietarySeats }} couvert(s) potentiellement concerné(s))</h2>
    @if ($dietary->isEmpty())
        <p class="muted">Aucun régime particulier signalé.</p>
    @else
        <table>
            <thead><tr><th>Invité</th><th>Table</th><th>Régime / allergie</th><th>Couverts</th></tr></thead>
            <tbody>
            @foreach ($dietary as $row)
                <tr>
                    <td>{{ $row['guest'] }}</td>
                    <td>{{ $row['table'] ?? '—' }}</td>
                    <td>{{ $row['restriction'] }}</td>
                    <td>{{ $row['seats'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="muted">« Couverts » : le régime est saisi pour l'invité ; précisez avec lui s'il concerne aussi ses accompagnants.</p>
    @endif
@endsection
