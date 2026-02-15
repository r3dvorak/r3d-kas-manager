@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>DNS-Einstellungen</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Hier sehen Sie die DNS-Eintraege aus dem lokalen DB-Snapshot. Sync holt die Zone(n) erneut aus der KAS-API.
</p>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif
@if(session('error'))
    <div class="uk-alert-danger" uk-alert><p>{{ session('error') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        <strong>DNS-Eintraege:</strong> {{ $records->total() }}
        @if($records->total() > 0)
            <span class="uk-text-muted">| Anzeigen {{ $records->firstItem() }} - {{ $records->lastItem() }} von {{ $records->total() }}</span>
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-default" href="{{ route('client.dns.preview') }}">Pruefung (KAS vs DB)</a>
        </div>
        <div>
            <form action="{{ route('client.dns.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('DNS-Snapshot aus KAS holen und DB aktualisieren? (kann etwas dauern)')">Sync jetzt</button>
            </form>
        </div>
    </div>
</div>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route('client.dns.index') }}">
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
                <th>Zone</th>
                <th class="uk-text-nowrap">Typ</th>
                <th>Name</th>
                <th>Data/Value</th>
                <th class="uk-text-nowrap">Aux</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $r)
                <tr>
                    <td class="uk-text-nowrap">{{ rtrim((string) $r->record_zone, '.') }}</td>
                    <td class="uk-text-nowrap">{{ $r->record_type ?: '—' }}</td>
                    <td class="uk-text-nowrap">{{ $r->record_name === '' ? '—' : $r->record_name }}</td>
                    <td style="max-width: 620px; white-space: normal;">{{ $r->record_data }}</td>
                    <td class="uk-text-nowrap uk-text-right">{{ (int) ($r->record_aux ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="uk-text-muted">Noch keine DNS-Daten in der DB. Bitte Sync ausfuehren.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $records->links() }}
</div>
@endsection

