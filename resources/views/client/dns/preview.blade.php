@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>DNS: Pruefung (KAS vs DB)</span></h1>

<p class="uk-text-small uk-text-muted">
    Vorschau der Differenzen pro Domain (vereinfachter Vergleich ueber Record-Fingerprints).
</p>

<div class="uk-card uk-card-default uk-card-body uk-margin">
    <div class="uk-text-small">
        <strong>Summe lokal:</strong> {{ $diff['total_local'] }}
        <span class="uk-text-muted">|</span>
        <strong>Summe remote:</strong> {{ $diff['total_remote'] }}
    </div>
</div>

<div class="uk-overflow-auto">
    <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
        <thead>
            <tr>
                <th>Domain</th>
                <th class="uk-text-nowrap uk-text-right">lokal</th>
                <th class="uk-text-nowrap uk-text-right">remote</th>
                <th class="uk-text-nowrap uk-text-right">to_add</th>
                <th class="uk-text-nowrap uk-text-right">to_remove</th>
            </tr>
        </thead>
        <tbody>
            @forelse($diff['domains'] as $row)
                <tr>
                    <td class="uk-text-nowrap"><strong>{{ $row['domain'] }}</strong></td>
                    <td class="uk-text-right">{{ $row['local'] }}</td>
                    <td class="uk-text-right">{{ $row['remote'] }}</td>
                    <td class="uk-text-right">{{ $row['to_add'] }}</td>
                    <td class="uk-text-right">{{ $row['to_remove'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="uk-text-muted">Keine Domains gefunden.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    <a class="uk-button uk-button-default" href="{{ route('client.dns.index') }}">Zurueck</a>
</div>
@endsection

