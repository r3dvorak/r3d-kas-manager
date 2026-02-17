@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Mailkonten (DB)</span></h1>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif
@if(session('error'))
    <div class="uk-alert-danger" uk-alert><p>{{ session('error') }}</p></div>
@endif

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route('admin.mailboxes.index') }}">
    <div class="uk-width-1-3@m">
        <select class="uk-select" name="kas_login">
            <option value="">Alle Clients</option>
            @foreach($clients as $c)
                <option value="{{ $c->account_login }}" {{ ($kasLogin===$c->account_login) ? 'selected' : '' }}>
                    {{ $c->account_comment }} ({{ $c->account_login }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="uk-width-1-3@m">
        <input class="uk-input" type="text" name="q" value="{{ $q }}" placeholder="Suche (email, domain, mail_login)...">
    </div>
    <div class="uk-width-auto@m">
        <button class="uk-button uk-button-primary" type="submit">Filter</button>
    </div>
    <div class="uk-width-auto@m">
        <a class="uk-button uk-button-default" href="{{ route('admin.mailboxes.create') }}">Neu</a>
    </div>
    <div class="uk-width-auto@m">
        @if($kasLogin)
            <a class="uk-button uk-button-default" href="{{ route('admin.mailboxes.preview', ['kas_login'=>$kasLogin]) }}">Pruefung (KAS vs DB)</a>
            <form action="{{ route('admin.mailboxes.sync') }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="kas_login" value="{{ $kasLogin }}">
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot ersetzen?')">Sync jetzt</button>
            </form>
        @endif
    </div>
</form>

<div class="uk-overflow-auto">
    <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
        <thead>
            <tr>
                <th>Email(s)</th>
                <th>Domain</th>
                <th>Mail-Login</th>
                <th>Spamfilter</th>
                <th>Quota</th>
                <th class="uk-text-nowrap">KAS Login</th>
                <th class="uk-text-nowrap">Aktion</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mailboxes as $m)
                <tr>
                    <td style="max-width: 520px; white-space: normal;">{{ $m->email }}</td>
                    <td class="uk-text-nowrap">{{ $m->domain ?: '—' }}</td>
                    <td class="uk-text-nowrap">{{ $m->mail_login }}</td>
                    <td class="uk-text-nowrap">{{ $m->spamfilterLabel() }}</td>
                    <td class="uk-text-nowrap">{{ $m->quotaRule() ?: '—' }}</td>
                    <td class="uk-text-nowrap">{{ $m->kas_login }}</td>
                    <td class="uk-text-nowrap">
                        <a class="uk-button uk-button-text" href="{{ route('admin.mailboxes.edit', $m) }}">Bearbeiten</a>
                        <form action="{{ route('admin.mailboxes.destroy', $m) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="uk-button uk-button-text uk-text-danger" type="submit" onclick="return confirm('Mailbox wirklich loeschen?')">Loeschen</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="uk-text-muted">Keine Mailkonten in der DB. Waehle einen Client und klicke Sync oder lege manuell an.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $mailboxes->links() }}
</div>
@endsection
