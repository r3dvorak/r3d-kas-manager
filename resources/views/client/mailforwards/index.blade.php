@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>E-Mail-Weiterleitung</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Verwenden Sie E-Mail-Weiterleitungen, um E-Mails, die an die Adresse der Weiterleitung gesendet werden, an andere E-Mail-Adressen weiterzuleiten.
    Beachten Sie bitte, dass Sie ueber eine Weiterleitung keine Mails senden koennen.
</p>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif
@if(session('error'))
    <div class="uk-alert-danger" uk-alert><p>{{ session('error') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        <strong>Angelegte E-Mail-Weiterleitungen:</strong> {{ $forwards->total() }}
        @if($forwards->total() > 0)
            <span class="uk-text-muted">| Anzeigen {{ $forwards->firstItem() }} - {{ $forwards->lastItem() }} von {{ $forwards->total() }}</span>
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-default" href="{{ route_w('client.mailforwards.preview') }}">Pruefung (KAS vs DB)</a>
        </div>
        <div>
            <form action="{{ route_w('client.mailforwards.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot ersetzen?')">Sync jetzt</button>
            </form>
        </div>
    </div>
</div>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route_w('client.mailforwards.index') }}">
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
                <th>Domain/Weiterleitung</th>
                <th class="uk-text-nowrap">Status</th>
                <th>Ziel</th>
                <th class="uk-text-nowrap">Aktion</th>
            </tr>
        </thead>
        <tbody>
            @php($lastDomain = null)
            @forelse($forwards as $f)
                @php($addr = (string) $f->mail_forward_address)
                @php($domainPart = str_contains($addr, '@') ? strtolower(explode('@', $addr, 2)[1] ?? '') : '—')

                @if($lastDomain !== ($domainPart !== '' ? $domainPart : '—'))
                    @php($lastDomain = ($domainPart !== '' ? $domainPart : '—'))
                    <tr class="uk-background-muted">
                        <td colspan="4" class="uk-text-bold">
                            {{ $lastDomain }}
                            @if(isset($domainTotals[strtolower($lastDomain)]) || isset($domainTotals[$lastDomain]))
                                @php($cnt = $domainTotals[strtolower($lastDomain)] ?? $domainTotals[$lastDomain] ?? null)
                                @if($cnt !== null)
                                    <span class="uk-text-muted">| Weiterl.: {{ $cnt }}</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endif

                <tr>
                    <td class="uk-text-nowrap">{{ $f->mail_forward_address }}</td>
                    <td class="uk-text-nowrap">
                        @if($f->spamfilterEnabled())
                            <span uk-icon="icon: shield"></span>
                        @else
                            <span class="uk-text-muted">—</span>
                        @endif
                    </td>
                    <td style="max-width: 620px; white-space: normal;">{{ $f->mail_forward_targets }}</td>
                    <td class="uk-text-nowrap uk-text-muted">
                        <span uk-icon="icon: pencil"></span>
                        <span uk-icon="icon: trash"></span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="uk-text-muted">Noch keine Daten in der DB. Bitte Sync ausfuehren.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $forwards->links() }}
</div>
@endsection
