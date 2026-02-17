@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Subdomain</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Eine Subdomain ist ein Teil einer Domain, der vor der eigentlichen Domain steht.
</p>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        <strong>Angelegte Subdomains:</strong> {{ $subdomains->total() }}
        @if($subdomains->total() > 0)
            <span class="uk-text-muted">| Anzeigen {{ $subdomains->firstItem() }} - {{ $subdomains->lastItem() }} von {{ $subdomains->total() }}</span>
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-default" href="{{ route_w('client.subdomains.preview') }}">Pruefung (KAS vs DB)</a>
        </div>
        <div>
            <form action="{{ route_w('client.subdomains.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot aktualisieren?')">Sync jetzt</button>
            </form>
        </div>
    </div>
</div>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route_w('client.subdomains.index') }}">
    <div class="uk-width-1-2@m">
        <input class="uk-input" type="text" name="q" value="{{ $q }}" placeholder="Suche...">
    </div>
    <div class="uk-width-1-4@m">
        <select class="uk-select" name="domain" onchange="this.form.submit()">
            <option value="">Alle Domains</option>
            @foreach($domainOptions as $d)
                <option value="{{ $d }}" @selected(strtolower($domain) === strtolower($d))>{{ $d }}</option>
            @endforeach
        </select>
    </div>
    <div class="uk-width-auto@m">
        <button class="uk-button uk-button-primary" type="submit">Suche</button>
    </div>
</form>

<div class="uk-overflow-auto">
    <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
        <thead>
            <tr>
                <th>Subdomain</th>
                <th>Ziel</th>
                <th class="uk-text-nowrap">PHP</th>
                <th class="uk-text-nowrap">SSL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subdomains as $s)
                <tr>
                    <td class="uk-text-nowrap"><strong>{{ $s->subdomain_full }}</strong></td>
                    <td style="max-width: 520px; white-space: normal;">{{ $s->subdomain_path ?: '—' }}</td>
                    <td class="uk-text-nowrap">{{ $s->php_version ?: '—' }}</td>
                    <td class="uk-text-nowrap">
                        @if((bool) $s->ssl_status)
                            <span class="uk-label uk-label-success">aktiv</span>
                        @else
                            <span class="uk-label">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="uk-text-muted">Noch keine Subdomains in der DB.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $subdomains->links() }}
</div>
@endsection

