@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Datenbanken</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Datenbanken (MariaDB) dieses Accounts aus dem lokalen DB-Snapshot.
</p>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif
@if(session('info'))
    <div class="uk-alert-primary" uk-alert><p>{{ session('info') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        <strong>Angelegte Datenbanken:</strong> {{ $databases->total() }}
        @if($databases->total() > 0)
            <span class="uk-text-muted">| Anzeigen {{ $databases->firstItem() }} - {{ $databases->lastItem() }} von {{ $databases->total() }}</span>
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-primary" href="{{ route_w('client.coming-soon', ['resource' => 'databases']) }}">NEU</a>
        </div>
        <div>
            <a class="uk-button uk-button-default" href="{{ route_w('client.databases.preview') }}"><span uk-icon="refresh" class="uk-margin-small-right"></span>Änderungen Prüfung (KAS vs DB)</a>
        </div>
        <div>
            <form action="{{ route_w('client.databases.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot aktualisieren?')"><span uk-icon="future" class="uk-margin-small-right"></span>Sync jetzt</button>
            </form>
        </div>
    </div>
</div>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route_w('client.databases.index') }}">
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
                <th>Datenbank</th>
                <th>Kommentar</th>
                <th>Hosts</th>
                <th class="uk-text-nowrap">Aktion</th>
            </tr>
        </thead>
        <tbody>
            @forelse($databases as $db)
                <tr>
                    <td class="uk-text-nowrap"><strong>{{ $db->database_login ?: '—' }}</strong></td>
                    <td style="max-width: 360px; white-space: normal;">{{ $db->database_comment ?: '—' }}</td>
                    <td style="max-width: 520px; white-space: normal;">{{ $db->database_allowed_hosts ?: '—' }}</td>
                    <td class="uk-text-nowrap uk-text-muted table-action-icons">
                        <a href="{{ route_w('client.launch.create', ['tool' => 'pma', 'database' => $db->id]) }}"
                           class="uk-icon-button action-icon-btn"
                           uk-icon="icon: sign-in"
                           title="phpMyAdmin"
                           data-launch-url="{{ route_w('client.launch.create', ['tool' => 'pma', 'database' => $db->id]) }}"
                           data-launch-tool="phpMyAdmin"
                           data-launch-login="{{ $db->database_login }}"
                           data-launch-target="{{ $db->database_login }}"
                           onclick="return openExternalLaunchModal(event, this);"></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="uk-text-muted">Noch keine Datenbanken in der DB. Bitte Sync ausfuehren.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $databases->links() }}
</div>
@endsection
