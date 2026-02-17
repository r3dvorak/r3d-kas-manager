@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Statistik: Pruefung (KAS vs DB)</span></h1>

@php
    $fmtGb = function (?int $kb): string {
        if ($kb === null) return '—';
        $gb = round(((float)$kb) / 1024 / 1024, 2);
        return number_format($gb, 2, ',', '.') . ' GB';
    };
@endphp

<div class="uk-overflow-auto">
    <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
        <thead>
            <tr>
                <th></th>
                <th class="uk-text-nowrap">lokal (DB)</th>
                <th class="uk-text-nowrap">remote (KAS)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Belegt</strong></td>
                <td>{{ $fmtGb($diff['local_used_kb']) }}</td>
                <td>{{ $fmtGb($diff['remote_used_kb']) }}</td>
            </tr>
            <tr>
                <td><strong>Moeglich</strong></td>
                <td>{{ $fmtGb($diff['local_max_kb']) }}</td>
                <td>{{ $fmtGb($diff['remote_max_kb']) }}</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    <a class="uk-button uk-button-default" href="{{ route_w('client.statistics.index') }}">Zurueck</a>
</div>
@endsection

