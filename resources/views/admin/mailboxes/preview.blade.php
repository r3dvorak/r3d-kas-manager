@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Pruefung Mailkonten (KAS vs DB)</span></h1>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route('admin.mailboxes.preview') }}">
    <div class="uk-width-1-2@m">
        <select class="uk-select" name="kas_login" required>
            @foreach($clients as $c)
                <option value="{{ $c->account_login }}" {{ ($kasLogin===$c->account_login) ? 'selected' : '' }}>
                    {{ $c->account_comment }} ({{ $c->account_login }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="uk-width-auto@m">
        <button class="uk-button uk-button-primary" type="submit">Neu pruefen</button>
    </div>
    <div class="uk-width-auto@m">
        <a class="uk-button uk-button-default" href="{{ route('admin.mailboxes.index', ['kas_login'=>$kasLogin]) }}">Zurueck</a>
    </div>
    <div class="uk-width-auto@m">
        <form action="{{ route('admin.mailboxes.sync') }}" method="POST" style="display:inline;">
            @csrf
            <input type="hidden" name="kas_login" value="{{ $kasLogin }}">
            <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot ersetzen?')">Sync jetzt</button>
        </form>
    </div>
</form>

@php
  $add = $diff['to_add'] ?? [];
  $rem = $diff['to_remove'] ?? [];
@endphp

<div class="uk-grid-small" uk-grid>
    <div class="uk-width-1-2@m">
        <div class="uk-card uk-card-default uk-card-body">
            <h3 class="uk-card-title">In KAS, fehlt in DB</h3>
            <div class="uk-text-muted">{{ count($add) }} Eintraege</div>
            <div class="uk-overflow-auto" style="max-height: 420px;">
                <ul class="uk-list uk-list-divider">
                    @foreach($add as $e)
                        <li class="uk-text-small">{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="uk-width-1-2@m">
        <div class="uk-card uk-card-default uk-card-body">
            <h3 class="uk-card-title">In DB, fehlt in KAS</h3>
            <div class="uk-text-muted">{{ count($rem) }} Eintraege</div>
            <div class="uk-overflow-auto" style="max-height: 420px;">
                <ul class="uk-list uk-list-divider">
                    @foreach($rem as $e)
                        <li class="uk-text-small">{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

