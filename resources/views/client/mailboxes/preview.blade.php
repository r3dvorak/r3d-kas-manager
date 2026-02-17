@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Pruefung Mailkonten (KAS vs DB)</span></h1>

<div class="uk-margin">
    <a class="uk-button uk-button-default" href="{{ route_w('client.mailboxes.index') }}">Zurueck</a>
    <form action="{{ route_w('client.mailboxes.sync') }}" method="POST" style="display:inline;">
        @csrf
        <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Sync von KAS holen und DB-Snapshot ersetzen?')">Sync jetzt</button>
    </form>
</div>

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

