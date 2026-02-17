@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>SSL-Schutz: Pruefung (KAS vs DB)</span></h1>

<p class="uk-text-small uk-text-muted">
    Anzeige von Domains, deren SSL-Flags (Proxy/IP/SNI) zwischen KAS (remote) und DB (lokal) abweichen.
</p>

<div class="uk-card uk-card-default uk-card-body uk-margin">
    <div class="uk-text-small">
        <strong>Abweichungen:</strong> {{ $diff['total'] }}
    </div>
</div>

<div class="uk-overflow-auto">
    <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
        <thead>
            <tr>
                <th>Domain</th>
                <th class="uk-text-nowrap">lokal</th>
                <th class="uk-text-nowrap">remote</th>
            </tr>
        </thead>
        <tbody>
            @forelse($diff['changed'] as $row)
                <tr>
                    <td class="uk-text-nowrap"><strong>{{ $row['domain'] }}</strong></td>
                    <td class="uk-text-nowrap">{{ $row['local'] }}</td>
                    <td class="uk-text-nowrap">{{ $row['remote'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="uk-text-muted">Keine Abweichungen.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    <a class="uk-button uk-button-default" href="{{ route_w('client.ssl.index') }}">Zurueck</a>
</div>
@endsection

