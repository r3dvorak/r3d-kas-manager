@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>SSL-Schutz</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Uebersicht der SSL-Einstellungen pro Domain (aus dem lokalen DB-Snapshot).
</p>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        <strong>Domains:</strong> {{ $domains->total() }}
        @if($domains->total() > 0)
            <span class="uk-text-muted">| Anzeigen {{ $domains->firstItem() }} - {{ $domains->lastItem() }} von {{ $domains->total() }}</span>
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-default" href="{{ route_w('client.ssl.preview') }}">Pruefung (KAS vs DB)</a>
        </div>
        <div>
            <form action="{{ route_w('client.ssl.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot aktualisieren?')">Sync jetzt</button>
            </form>
        </div>
    </div>
</div>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route_w('client.ssl.index') }}">
    <div class="uk-width-1-2@m">
        <input class="uk-input" type="text" name="q" value="{{ $q }}" placeholder="Suche...">
    </div>
    <div class="uk-width-auto@m">
        <button class="uk-button uk-button-primary" type="submit">Suche</button>
    </div>
</form>

<div class="uk-overflow-auto">
    <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
        <thead>
            <tr>
                <th>Domain</th>
                <th class="uk-text-nowrap">Status</th>
                <th class="uk-text-nowrap">Proxy</th>
                <th class="uk-text-nowrap">IP</th>
                <th class="uk-text-nowrap">SNI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($domains as $d)
                @php($proxy = (string) ($d->ssl_proxy ?? 'N'))
                @php($ip = (string) ($d->ssl_certificate_ip ?? 'N'))
                @php($sni = (string) ($d->ssl_certificate_sni ?? 'N'))
                @php($active = strtoupper(trim($proxy)) === 'Y' || strtoupper(trim($ip)) === 'Y' || strtoupper(trim($sni)) === 'Y')
                <tr>
                    <td class="uk-text-nowrap"><strong>{{ $d->domain_full ?: $d->label() }}</strong></td>
                    <td class="uk-text-nowrap">
                        @if($active)
                            <span class="uk-label uk-label-success">aktiv</span>
                        @else
                            <span class="uk-label">—</span>
                        @endif
                    </td>
                    <td class="uk-text-nowrap">{{ strtoupper(trim($proxy)) === 'Y' ? '✓' : '—' }}</td>
                    <td class="uk-text-nowrap">{{ strtoupper(trim($ip)) === 'Y' ? '✓' : '—' }}</td>
                    <td class="uk-text-nowrap">{{ strtoupper(trim($sni)) === 'Y' ? '✓' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="uk-text-muted">Noch keine Domains in der DB.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $domains->links() }}
</div>
@endsection

