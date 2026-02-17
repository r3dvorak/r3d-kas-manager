@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>E-Mail-Postfach</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Verwenden Sie E-Mail-Postfaecher, um mit Ihren E-Mail-Adressen E-Mails senden und empfangen zu koennen.
    Der Postein- und Ausgangsserver lautet:
    <strong>{{ $client?->server_hostname ?: '—' }}</strong>
</p>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif
@if(session('error'))
    <div class="uk-alert-danger" uk-alert><p>{{ session('error') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        <strong>Angelegte Postfaecher:</strong> {{ $mailboxes->total() }}
        @if($mailboxes->total() > 0)
            <span class="uk-text-muted">| Anzeigen {{ $mailboxes->firstItem() }} - {{ $mailboxes->lastItem() }} von {{ $mailboxes->total() }}</span>
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-primary" href="{{ route_w('client.mailboxes.create') }}">Neues Postfach</a>
        </div>
        <div>
            <a class="uk-button uk-button-default" href="{{ route_w('client.mailboxes.preview') }}">Pruefung (KAS vs DB)</a>
        </div>
        <div>
            <form action="{{ route_w('client.mailboxes.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot ersetzen?')">Sync jetzt</button>
            </form>
        </div>
    </div>
</div>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route_w('client.mailboxes.index') }}">
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
                <th>Domain/Postfach</th>
                <th class="uk-text-nowrap">Status</th>
                <th class="uk-text-nowrap">Benutzername</th>
                <th class="uk-text-nowrap">Speicheruebersicht</th>
                <th class="uk-text-nowrap">Aktion</th>
            </tr>
        </thead>
        <tbody>
            @php($lastDomain = null)
            @forelse($mailboxes as $m)
                @if($lastDomain !== ($m->domain ?: '—'))
                    @php($lastDomain = ($m->domain ?: '—'))
                    <tr class="uk-background-muted">
                        <td colspan="5" class="uk-text-bold">
                            {{ $lastDomain }}
                            @if(isset($domainTotals[strtolower($lastDomain)]) || isset($domainTotals[$lastDomain]))
                                @php($cnt = $domainTotals[strtolower($lastDomain)] ?? $domainTotals[$lastDomain] ?? null)
                                @if($cnt !== null)
                                    <span class="uk-text-muted">| E-Mail-Postfaecher: {{ $cnt }}</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endif

                <tr class="{{ $m->mailboxAccessRowClass() }}">
                    <td style="max-width: 520px; white-space: normal;">
                        {{ $m->email }}
                    </td>
                    <td class="uk-text-nowrap">
                        @if($m->spamfilterLabel() !== '—')
                            <span uk-icon="icon: shield"></span>
                        @else
                            <span class="uk-text-muted">—</span>
                        @endif
                    </td>
                    <td class="uk-text-nowrap">{{ $m->mail_login }}</td>
                    <td class="uk-text-nowrap">
                        @php($mb = $m->usedSpaceMb())
                        @php($gb = $mb === null ? null : round($mb / 1024, 2))
                        {{ $gb === null ? '—' : number_format($gb, 2, ',', '.') . ' GB' }}
                        @if($mb !== null)
                            <div class="uk-text-muted uk-text-small">{{ number_format($mb, 0, ',', '.') }} MB</div>
                        @endif
                    </td>
                    <td class="uk-text-nowrap uk-text-muted table-action-icons">
                        <a href="{{ route_w('client.mailboxes.edit', $m) }}" class="uk-icon-button action-icon-btn" uk-icon="icon: pencil" title="Bearbeiten"></a>
                        <form action="{{ route_w('client.mailboxes.toggle-state', $m) }}" method="POST" class="table-action-form">
                            @csrf
                            <button
                                class="uk-icon-button action-icon-btn mailbox-state-icon-{{ $m->mailboxAccessState() }}"
                                type="submit"
                                uk-icon="icon: {{ $m->mailboxAccessIcon() }}"
                                title="Status: {{ $m->mailboxAccessLabel() }} (klicken zum Wechseln)">
                            </button>
                        </form>
                        <form action="{{ route_w('client.mailboxes.destroy', $m) }}" method="POST" class="table-action-form">
                            @csrf
                            @method('DELETE')
                            <button class="uk-icon-button action-icon-btn action-icon-btn-danger" uk-icon="icon: trash" type="submit" title="Loeschen" onclick="return confirmDeleteTwice('Postfach wirklich loeschen?', 'LOESCHEN')"></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="uk-text-muted">Noch keine Daten in der DB. Bitte Sync ausfuehren.</td></tr>
            @endforelse
        </tbody>
        @if($mailboxes->total() > 0)
            <tfoot>
                <tr class="uk-background-muted">
                    <td colspan="3" class="uk-text-bold">Summe (alle Postfaecher)</td>
                    <td class="uk-text-nowrap uk-text-bold">
                        {{ number_format((float)($totalUsedGb ?? 0), 2, ',', '.') }} GB
                        <div class="uk-text-muted uk-text-small">
                            {{ number_format((float)($totalUsedMb ?? 0), 0, ',', '.') }} MB
                        </div>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<div class="uk-margin-top">
    {{ $mailboxes->links() }}
</div>
@endsection
