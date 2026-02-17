{{-- resources/views/client/dashboard.blade.php --}}
{{-- 
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.28.11-alpha
 * @date      2025-09-26
 *
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 *
 * resources\views\client\dashboard.blade.php
--}}

@extends('layouts.app')

@section('content')
@php
    $client = Auth::guard('kas_client')->user();
    $kasLogin = strtolower((string) ($client?->account_login ?? ''));
    $displayName = (string) ($client?->account_comment ?? $client?->name ?? $kasLogin);
    $serverHostname = (string) ($client?->server_hostname ?? '');
    $serverIp = (string) ($client?->server_ip ?? '');
    $rootPath = $kasLogin !== '' ? "/www/htdocs/{$kasLogin}/" : '—';
    $clientId = (int) ($client?->id ?? 0);

    $domainsCount = 0;
    $subdomainsCount = 0;
    $mailboxesCount = 0;
    $forwardsCount = 0;
    $maxDomains = (int) ($client?->max_domain ?? 0);
    $maxSubdomains = (int) ($client?->max_subdomain ?? 0);
    $maxMailboxes = (int) ($client?->max_mail_account ?? 0);
    $maxForwards = (int) ($client?->max_mail_forward ?? 0);
    $maxWebspaceMb = (float) ($client?->max_webspace ?? 0);
    $usedWebspaceGbFromStats = ($client && method_exists($client, 'usedSpaceGb')) ? (float) $client->usedSpaceGb() : 0.0;
    $mailboxesUsedMb = 0.0;
    $usedWebspaceGbFromMailboxes = 0.0;
    $usedWebspaceGb = 0.0;
    $usedWebspaceSource = '—';
    $maxWebspaceGb = $maxWebspaceMb > 0 ? round($maxWebspaceMb / 1024, 2) : 0.0;
    $freeWebspaceGb = 0.0;

    if ($clientId > 0) {
        $domainsCount = \App\Models\KasDomain::where('kas_client_id', $clientId)->whereNull('deleted_at')->count();
        $subdomainsCount = \App\Models\KasSubdomain::where('kas_client_id', $clientId)->whereNull('deleted_at')->count();
    }

    if ($kasLogin !== '') {
        $mailboxesCount = \App\Models\KasMailAccount::where('kas_login', $kasLogin)->where('status', 'active')->count();
        $forwardsCount = \App\Models\KasMailForward::where('kas_login', $kasLogin)->where('status', 'active')->count();
        $mailboxesUsedMb = \App\Models\KasMailAccount::where('kas_login', $kasLogin)->where('status', 'active')->get()->sum(fn($m) => (float)($m->usedSpaceMb() ?? 0));
    }

    $usedWebspaceGbFromMailboxes = $mailboxesUsedMb > 0 ? round($mailboxesUsedMb / 1024, 2) : 0.0;
    $usedWebspaceGb = $usedWebspaceGbFromStats > 0 ? $usedWebspaceGbFromStats : $usedWebspaceGbFromMailboxes;
    $usedWebspaceSource = $usedWebspaceGbFromStats > 0 ? 'KAS get_space' : ($usedWebspaceGbFromMailboxes > 0 ? 'Summe Postfaecher (DB)' : '—');
    $freeWebspaceGb = ($maxWebspaceGb > 0) ? max(0.0, round($maxWebspaceGb - $usedWebspaceGb, 2)) : 0.0;

    $latestSpaceReport = \App\Models\KasSpaceReport::where('kas_login', $kasLogin)->orderByDesc('measured_at')->first();
    $lastSpaceReportAt = $latestSpaceReport?->measured_at;

    $usedMailGb = null;
    $usedDbGb = null;
    $usedHtdocsGb = null;

    $raw = is_array($latestSpaceReport?->data_json ?? null) ? $latestSpaceReport->data_json : null;
    $ri = is_array($raw) ? ($raw['Response']['ReturnInfo'] ?? $raw['ReturnInfo'] ?? null) : null;
    $info = (is_array($ri) && isset($ri[0]) && is_array($ri[0])) ? $ri[0] : (is_array($ri) ? $ri : null);

    if (is_array($info)) {
        if (is_numeric($info['used_mailaccount_space'] ?? null)) $usedMailGb = round(((float)$info['used_mailaccount_space']) / 1024 / 1024, 2);
        if (is_numeric($info['used_htdocs_space'] ?? null)) $usedHtdocsGb = round(((float)$info['used_htdocs_space']) / 1024 / 1024, 2);
        if (is_numeric($info['used_database_space'] ?? null)) $usedDbGb = round(((float)$info['used_database_space']) / 1024 / 1024, 2);
    }
@endphp

<div class="uk-container">
    <h1 class="uk-heading-line"><span>Willkommen in der technischen Verwaltung</span></h1>
    <p class="uk-text-muted uk-margin-remove-top">
        Wichtiger Hinweis: Einstellungen werden nicht sofort auf dem Server umgesetzt. Es kann einige Minuten dauern, bis Aenderungen wirksam werden.
    </p>

    <div class="uk-alert-primary" uk-alert>
        <p class="uk-margin-remove">
            <strong>KAS:</strong> {{ $kasLogin ?? '—' }}
            <span class="uk-text-muted">|</span>
            <strong>Account:</strong> {{ $displayName ?: '—' }}
        </p>
    </div>

    <div class="uk-card uk-card-default uk-card-body uk-margin">
        <div class="uk-flex uk-flex-between uk-flex-middle">
            <h3 class="uk-card-title uk-margin-remove">Direktlinks</h3>
            <div class="uk-text-small uk-text-muted">
                {{ $serverHostname ?: '—' }}@if($serverIp) · {{ $serverIp }}@endif
            </div>
        </div>

        <div class="uk-grid-small uk-child-width-auto@s uk-margin-small-top" uk-grid>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.dns.index') }}">DNS-Einstellungen</a></div>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.mailboxes.index') }}">E-Mail-Postfach</a></div>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.mailforwards.index') }}">E-Mail-Weiterleitung</a></div>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.domains.index') }}">Domain</a></div>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.recipes.index') }}">Rezepte</a></div>
        </div>

        <div class="uk-grid-small uk-child-width-1-3@m uk-margin-top" uk-grid>
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-padding-small">
                    <div class="uk-text-small uk-text-muted">aktuelle Server-IP</div>
                    <div class="uk-text-bold">{{ $serverIp ?: '—' }}</div>
                </div>
            </div>
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-padding-small">
                    <div class="uk-text-small uk-text-muted">Servername</div>
                    <div class="uk-text-bold">{{ $serverHostname ?: '—' }}</div>
                </div>
            </div>
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-padding-small">
                    <div class="uk-text-small uk-text-muted">Stammverzeichnis</div>
                    <div class="uk-text-bold uk-text-break">{{ $rootPath }}</div>
                </div>
            </div>
        </div>

        <h4 class="uk-heading-bullet uk-margin-top">Ressourcen</h4>
        <div class="uk-overflow-auto">
            <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
                <thead>
                    <tr>
                        <th>Ressourcen</th>
                        <th class="uk-text-nowrap uk-text-right">angelegt</th>
                        <th class="uk-text-nowrap uk-text-right">reserviert</th>
                        <th class="uk-text-nowrap uk-text-right">verbleibend</th>
                        <th class="uk-text-nowrap uk-text-right">moeglich</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Domain</td>
                        <td class="uk-text-right">{{ $domainsCount }}</td>
                        <td class="uk-text-right">0</td>
                        <td class="uk-text-right">{{ $maxDomains > 0 ? max(0, $maxDomains - $domainsCount) : '—' }}</td>
                        <td class="uk-text-right">{{ $maxDomains ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td>Subdomains</td>
                        <td class="uk-text-right">{{ $subdomainsCount }}</td>
                        <td class="uk-text-right">0</td>
                        <td class="uk-text-right">{{ $maxSubdomains > 0 ? max(0, $maxSubdomains - $subdomainsCount) : '—' }}</td>
                        <td class="uk-text-right">{{ $maxSubdomains ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td>E-Mail-Postfaecher</td>
                        <td class="uk-text-right">{{ $mailboxesCount }}</td>
                        <td class="uk-text-right">0</td>
                        <td class="uk-text-right">{{ $maxMailboxes > 0 ? max(0, $maxMailboxes - $mailboxesCount) : '—' }}</td>
                        <td class="uk-text-right">{{ $maxMailboxes ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td>E-Mail-Weiterleitungen</td>
                        <td class="uk-text-right">{{ $forwardsCount }}</td>
                        <td class="uk-text-right">0</td>
                        <td class="uk-text-right">{{ $maxForwards > 0 ? max(0, $maxForwards - $forwardsCount) : '—' }}</td>
                        <td class="uk-text-right">{{ $maxForwards ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td>Speicherplatz</td>
                        <td class="uk-text-right">
                            {{ number_format($usedWebspaceGb, 2, ',', '.') }} GB
                            @if($usedWebspaceSource !== '—')
                                <div class="uk-text-muted uk-text-small">Quelle: {{ $usedWebspaceSource }}</div>
                            @endif
                        </td>
                        <td class="uk-text-right">
                            0,00 GB
                            @if($usedMailGb !== null || $usedHtdocsGb !== null || $usedDbGb !== null)
                                <div class="uk-text-muted uk-text-small">
                                    @if($usedMailGb !== null) E-Mail: {{ number_format($usedMailGb, 2, ',', '.') }} GB @endif
                                    @if($usedHtdocsGb !== null) | htdocs: {{ number_format($usedHtdocsGb, 2, ',', '.') }} GB @endif
                                    @if($usedDbGb !== null) | DB: {{ number_format($usedDbGb, 2, ',', '.') }} GB @endif
                                </div>
                            @endif
                        </td>
                        <td class="uk-text-right">{{ $maxWebspaceGb > 0 ? number_format($freeWebspaceGb, 2, ',', '.') . ' GB' : '—' }}</td>
                        <td class="uk-text-right">{{ $maxWebspaceGb > 0 ? number_format($maxWebspaceGb, 2, ',', '.') . ' GB' : '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="uk-text-small uk-text-muted uk-margin-small-top">
            Messung vom {{ $lastSpaceReportAt ? \Carbon\Carbon::parse($lastSpaceReportAt)->format('d.m.Y H:i') : now()->format('d.m.Y H:i') }} Uhr
        </div>
    </div>

    <div class="uk-margin-top">
        <form action="{{ route_w('logout') }}" method="POST">
            @csrf
            <button type="submit" class="uk-button uk-button-danger">Abmelden</button>
        </form>
    </div>
</div>
@endsection
