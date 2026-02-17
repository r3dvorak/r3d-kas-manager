@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Statistik</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Speicherplatzuebersicht (aus dem lokalen DB-Snapshot). Sync holt aktuelle Werte aus der KAS-API.
</p>

@if(session('success'))
    <div class="uk-alert-success" uk-alert><p>{{ session('success') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        @if($latest)
            <strong>Messung:</strong> {{ $latest->measured_at?->format('d.m.Y H:i') }} Uhr
        @else
            <strong>Messung:</strong> —
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-default" href="{{ route_w('client.statistics.preview') }}"><span uk-icon="refresh" class="uk-margin-small-right"></span>Änderungen Prüfung (KAS vs DB)</a>
        </div>
        <div>
            <form action="{{ route_w('client.statistics.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('Aktuelle Statistik aus KAS holen und DB-Snapshot aktualisieren?')"><span uk-icon="future" class="uk-margin-small-right"></span>Sync jetzt</button>
            </form>
        </div>
    </div>
</div>

@php
    $usedKb = $latest?->used_kb;
    $maxKb  = $latest?->max_kb;

    // Backward-compatible fallback: older snapshots might have NULL columns but contain the raw ReturnInfo.
    if ($latest && (!is_numeric($usedKb) || !is_numeric($maxKb)) && is_array($latest->data_json ?? null)) {
        $raw = $latest->data_json;
        $ri = $raw['Response']['ReturnInfo'] ?? $raw['ReturnInfo'] ?? null;
        $info = (is_array($ri) && isset($ri[0]) && is_array($ri[0])) ? $ri[0] : (is_array($ri) ? $ri : null);
        if (is_array($info)) {
            if (!is_numeric($usedKb) && is_numeric($info['used_webspace'] ?? null)) $usedKb = (int) $info['used_webspace'];
            if (!is_numeric($maxKb) && is_numeric($info['max_webspace'] ?? null))  $maxKb  = (int) $info['max_webspace'];
        }
    }

    $usedGb = is_numeric($usedKb) ? round(((float)$usedKb) / 1024 / 1024, 2) : null;
    $maxGb  = is_numeric($maxKb) ? round(((float)$maxKb) / 1024 / 1024, 2) : null;
@endphp

<div class="uk-card uk-card-default uk-card-body uk-margin">
    <div class="uk-grid-small uk-child-width-1-3@m" uk-grid>
        <div>
            <div class="uk-text-small uk-text-muted">Belegt</div>
            <div class="uk-text-bold">{{ $usedGb === null ? '—' : number_format($usedGb, 2, ',', '.') . ' GB' }}</div>
        </div>
        <div>
            <div class="uk-text-small uk-text-muted">Moeglich</div>
            <div class="uk-text-bold">{{ $maxGb === null ? '—' : number_format($maxGb, 2, ',', '.') . ' GB' }}</div>
        </div>
        <div>
            <div class="uk-text-small uk-text-muted">Verbleibend</div>
            <div class="uk-text-bold">
                @if($usedGb !== null && $maxGb !== null)
                    {{ number_format(max(0, $maxGb - $usedGb), 2, ',', '.') }} GB
                @else
                    —
                @endif
            </div>
        </div>
    </div>
</div>

<div class="uk-text-small uk-text-muted">
    Hinweis: Die Darstellung ist konservativ. Details (htdocs/E-Mail/Datenbanken) koennen wir spaeter aus <code>get_space --show_details=Y</code> ableiten.
</div>
@endsection
