@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>FTP</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Verwenden Sie einen FTP-Zugang, um Dateien zwischen Computer und Server zu uebertragen.
</p>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        <strong>Angelegte FTP-Nutzer:</strong> {{ $ftpusers->total() }}
        @if($ftpusers->total() > 0)
            <span class="uk-text-muted">| Anzeigen {{ $ftpusers->firstItem() }} - {{ $ftpusers->lastItem() }} von {{ $ftpusers->total() }}</span>
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-default" href="{{ route_w('client.ftp.preview') }}">Pruefung (KAS vs DB)</a>
        </div>
        <div>
            <form action="{{ route_w('client.ftp.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot aktualisieren?')">Sync jetzt</button>
            </form>
        </div>
    </div>
</div>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route_w('client.ftp.index') }}">
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
                <th>Benutzername</th>
                <th>Verzeichnis</th>
                <th class="uk-text-nowrap">R</th>
                <th class="uk-text-nowrap">W</th>
                <th class="uk-text-nowrap">L</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ftpusers as $u)
                <tr>
                    <td class="uk-text-nowrap"><strong>{{ $u->ftp_login ?: '—' }}</strong></td>
                    <td style="max-width: 520px; white-space: normal;">{{ $u->ftp_path ?: '—' }}</td>
                    <td class="uk-text-nowrap uk-text-center">{{ strtoupper((string) $u->perm_read) === 'Y' ? '✓' : '—' }}</td>
                    <td class="uk-text-nowrap uk-text-center">{{ strtoupper((string) $u->perm_write) === 'Y' ? '✓' : '—' }}</td>
                    <td class="uk-text-nowrap uk-text-center">{{ strtoupper((string) $u->perm_list) === 'Y' ? '✓' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="uk-text-muted">Noch keine FTP-Daten in der DB. Bitte Sync ausfuehren.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $ftpusers->links() }}
</div>
@endsection

