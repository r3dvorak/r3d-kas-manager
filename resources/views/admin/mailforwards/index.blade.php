@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Weiterleitungen (DB)</span></h1>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif
@if(session('error'))
    <div class="uk-alert-danger" uk-alert><p>{{ session('error') }}</p></div>
@endif

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route('admin.mailforwards.index') }}">
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
        <input class="uk-input" type="text" name="q" value="{{ $q }}" placeholder="Suche (from, target)...">
    </div>
    <div class="uk-width-auto@m">
        <button class="uk-button uk-button-primary" type="submit">Filter</button>
    </div>
    <div class="uk-width-auto@m">
        <a class="uk-button uk-button-default" href="{{ route('admin.mailforwards.create') }}">Neu</a>
    </div>
    <div class="uk-width-auto@m">
        @if($kasLogin)
            <a class="uk-button uk-button-default" href="{{ route('admin.mailforwards.preview', ['kas_login'=>$kasLogin]) }}">Pruefung (KAS vs DB)</a>
            <form action="{{ route('admin.mailforwards.sync') }}" method="POST" style="display:inline;">
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
                <th>Von</th>
                <th>Ziele</th>
                <th>Spamfilter</th>
                <th class="uk-text-nowrap">KAS Login</th>
                <th class="uk-text-nowrap">Aktion</th>
            </tr>
        </thead>
        <tbody>
            @forelse($forwards as $f)
                <tr>
                    <td class="uk-text-nowrap">{{ $f->mail_forward_address }}</td>
                    <td style="max-width: 620px; white-space: normal;">{{ $f->mail_forward_targets }}</td>
                    <td class="uk-text-nowrap">
                        @if($f->spamfilterEnabled())
                            <span class="uk-label uk-label-success">an</span>
                        @else
                            <span class="uk-label">aus</span>
                        @endif
                    </td>
                    <td class="uk-text-nowrap">{{ $f->kas_login }}</td>
                    <td class="uk-text-nowrap">
                        <a class="uk-button uk-button-text" href="{{ route('admin.mailforwards.edit', $f) }}">Bearbeiten</a>
                        <form action="{{ route('admin.mailforwards.destroy', $f) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="uk-button uk-button-text uk-text-danger" type="submit" onclick="return confirm('Weiterleitung wirklich loeschen?')">Loeschen</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="uk-text-muted">Keine Weiterleitungen in der DB. Waehle einen Client und klicke Sync oder lege manuell an.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $forwards->links() }}
</div>
@endsection
