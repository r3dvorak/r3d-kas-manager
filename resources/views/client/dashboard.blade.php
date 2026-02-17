{{-- resources/views/client/dashboard.blade.php --}}
{{-- 
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.30.1-alpha
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
    if ($serverHostname === '' && $kasLogin !== '') {
        $serverHostname = $kasLogin . '.kasserver.com';
    }
    if ($serverIp === '' && $serverHostname !== '') {
        $resolved = gethostbyname($serverHostname);
        if ($resolved !== $serverHostname) {
            $serverIp = $resolved;
        }
    }
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
    $domainsReserved = 0;
    $subdomainsReserved = 0;
    $mailboxesReserved = 0;
    $forwardsReserved = 0;
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
        $domainsReserved = \App\Models\KasDomain::where('kas_client_id', $clientId)->whereNotNull('deleted_at')->count();
        $subdomainsReserved = \App\Models\KasSubdomain::where('kas_client_id', $clientId)->whereNotNull('deleted_at')->count();
    }

    if ($kasLogin !== '') {
        $mailboxesCount = \App\Models\KasMailAccount::where('kas_login', $kasLogin)->where('status', 'active')->count();
        $forwardsCount = \App\Models\KasMailForward::where('kas_login', $kasLogin)->where('status', 'active')->count();
        $mailboxesReserved = \App\Models\KasMailAccount::where('kas_login', $kasLogin)->where('status', '!=', 'active')->count();
        $forwardsReserved = \App\Models\KasMailForward::where('kas_login', $kasLogin)->where('status', '!=', 'active')->count();
        $mailboxesUsedMb = \App\Models\KasMailAccount::where('kas_login', $kasLogin)->where('status', 'active')->get()->sum(fn($m) => (float)($m->usedSpaceMb() ?? 0));
    }

    $usedWebspaceGbFromMailboxes = $mailboxesUsedMb > 0 ? round($mailboxesUsedMb / 1024, 2) : 0.0;
    $usedWebspaceGb = $usedWebspaceGbFromStats > 0 ? $usedWebspaceGbFromStats : $usedWebspaceGbFromMailboxes;
    $usedWebspaceSource = $usedWebspaceGbFromStats > 0
        ? __('ui.client.resources.source_kas')
        : ($usedWebspaceGbFromMailboxes > 0 ? __('ui.client.resources.source_db_sum') : __('ui.client.none'));
    $freeWebspaceGb = ($maxWebspaceGb > 0) ? max(0.0, round($maxWebspaceGb - $usedWebspaceGb, 2)) : 0.0;
    $usedReservedWebspaceGb = 0.0;

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
    $usedReservedWebspaceGb = round((float)($usedMailGb ?? 0) + (float)($usedHtdocsGb ?? 0) + (float)($usedDbGb ?? 0), 2);

    $locale = app()->getLocale();
    $decimalSep = $locale === 'en' ? '.' : ',';
    $thousandSep = $locale === 'en' ? ',' : '.';
    $fmtInt = static fn ($n): string => number_format((int) $n, 0, $decimalSep, $thousandSep);
    $fmtGb = static fn ($n): string => number_format((float) $n, 2, $decimalSep, $thousandSep) . ' GB';
@endphp

<div class="uk-container">
    <h1 class="uk-heading-line"><span>{{ __('ui.client.dashboard_title') }}</span></h1>
    <p class="uk-text-muted uk-margin-remove-top">
        {{ __('ui.client.dashboard_hint') }}
    </p>

    <div class="uk-alert-primary" uk-alert>
        <p class="uk-margin-remove">
            <strong>KAS:</strong> {{ $kasLogin ?? '—' }}
            <span class="uk-text-muted">|</span>
            <strong>{{ __('ui.common.account') }}:</strong> {{ $displayName ?: '—' }}
        </p>
    </div>

    <div class="uk-card uk-card-default uk-card-body uk-margin">
        <div class="uk-flex uk-flex-between uk-flex-middle">
            <h3 class="uk-card-title uk-margin-remove">{{ __('ui.client.quick_links') }}</h3>
            <div class="uk-text-small uk-text-muted">
                {{ $serverHostname ?: '—' }}@if($serverIp) · {{ $serverIp }}@endif
            </div>
        </div>

        <div class="uk-grid-small uk-child-width-auto@s uk-margin-small-top" uk-grid>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.dns.index') }}">{{ __('ui.client.dns_settings') }}</a></div>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.mailboxes.index') }}">{{ __('ui.client.mailbox') }}</a></div>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.mailforwards.index') }}">{{ __('ui.client.mailforward') }}</a></div>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.domains.index') }}">{{ __('ui.nav.domain') }}</a></div>
            <div><a class="uk-button uk-button-default" href="{{ route_w('client.recipes.index') }}">{{ __('ui.nav.recipes') }}</a></div>
            <div><a class="uk-button uk-button-secondary" href="{{ route_w('client.launch.create', ['tool' => 'webmail']) }}" target="_blank" rel="noopener noreferrer">Webmail</a></div>
            <div><a class="uk-button uk-button-secondary" href="{{ route_w('client.launch.create', ['tool' => 'pma']) }}" target="_blank" rel="noopener noreferrer">phpMyAdmin</a></div>
        </div>

        <div class="uk-grid-small uk-child-width-1-3@m uk-margin-top" uk-grid>
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-padding-small">
                    <div class="uk-text-small uk-text-muted">{{ __('ui.client.server_ip') }}</div>
                    <div class="uk-text-bold">{{ $serverIp ?: '—' }}</div>
                </div>
            </div>
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-padding-small">
                    <div class="uk-text-small uk-text-muted">{{ __('ui.client.server_name') }}</div>
                    <div class="uk-text-bold">{{ $serverHostname ?: '—' }}</div>
                </div>
            </div>
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-padding-small">
                    <div class="uk-text-small uk-text-muted">{{ __('ui.client.root_path') }}</div>
                    <div class="uk-text-bold uk-text-break">{{ $rootPath }}</div>
                </div>
            </div>
        </div>

        <h4 class="uk-heading-bullet uk-margin-top">{{ __('ui.common.resource') }}</h4>
        <div class="uk-overflow-auto">
            <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
                <thead>
                    <tr>
                        <th>{{ __('ui.common.resource') }}</th>
                        <th class="uk-text-nowrap uk-text-right">{{ __('ui.common.created') }}</th>
                        <th class="uk-text-nowrap uk-text-right">{{ __('ui.common.reserved') }}</th>
                        <th class="uk-text-nowrap uk-text-right">{{ __('ui.common.remaining') }}</th>
                        <th class="uk-text-nowrap uk-text-right">{{ __('ui.common.possible') }}</th>
                        <th class="uk-text-nowrap uk-text-right">{{ __('ui.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('ui.client.resources.domains') }}</td>
                        <td class="uk-text-right">{{ $fmtInt($domainsCount) }}</td>
                        <td class="uk-text-right">{{ $fmtInt($domainsReserved) }}</td>
                        <td class="uk-text-right">{{ $maxDomains > 0 ? max(0, $maxDomains - $domainsCount - $domainsReserved) : '—' }}</td>
                        <td class="uk-text-right">{{ $maxDomains ?: '—' }}</td>
                        <td class="uk-text-right uk-text-nowrap">
                            <a class="uk-icon-button" href="{{ route_w('client.domains.preview') }}" uk-icon="icon: refresh" title="{{ __('ui.common.check_changes') }}"></a>
                            <form action="{{ route_w('client.domains.sync') }}" method="POST" class="uk-display-inline">
                                @csrf
                                <button class="uk-icon-button" type="submit" uk-icon="icon: future" title="{{ __('ui.common.sync_now') }}"></button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ __('ui.client.resources.subdomains') }}</td>
                        <td class="uk-text-right">{{ $fmtInt($subdomainsCount) }}</td>
                        <td class="uk-text-right">{{ $fmtInt($subdomainsReserved) }}</td>
                        <td class="uk-text-right">{{ $maxSubdomains > 0 ? max(0, $maxSubdomains - $subdomainsCount - $subdomainsReserved) : '—' }}</td>
                        <td class="uk-text-right">{{ $maxSubdomains ?: '—' }}</td>
                        <td class="uk-text-right uk-text-nowrap">
                            <a class="uk-icon-button" href="{{ route_w('client.subdomains.preview') }}" uk-icon="icon: refresh" title="{{ __('ui.common.check_changes') }}"></a>
                            <form action="{{ route_w('client.subdomains.sync') }}" method="POST" class="uk-display-inline">
                                @csrf
                                <button class="uk-icon-button" type="submit" uk-icon="icon: future" title="{{ __('ui.common.sync_now') }}"></button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ __('ui.client.resources.mailboxes') }}</td>
                        <td class="uk-text-right">{{ $fmtInt($mailboxesCount) }}</td>
                        <td class="uk-text-right">{{ $fmtInt($mailboxesReserved) }}</td>
                        <td class="uk-text-right">{{ $maxMailboxes > 0 ? max(0, $maxMailboxes - $mailboxesCount - $mailboxesReserved) : '—' }}</td>
                        <td class="uk-text-right">{{ $maxMailboxes ?: '—' }}</td>
                        <td class="uk-text-right uk-text-nowrap">
                            <a class="uk-icon-button" href="{{ route_w('client.mailboxes.preview') }}" uk-icon="icon: refresh" title="{{ __('ui.common.check_changes') }}"></a>
                            <form action="{{ route_w('client.mailboxes.sync') }}" method="POST" class="uk-display-inline">
                                @csrf
                                <button class="uk-icon-button" type="submit" uk-icon="icon: future" title="{{ __('ui.common.sync_now') }}"></button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ __('ui.client.resources.forwards') }}</td>
                        <td class="uk-text-right">{{ $fmtInt($forwardsCount) }}</td>
                        <td class="uk-text-right">{{ $fmtInt($forwardsReserved) }}</td>
                        <td class="uk-text-right">{{ $maxForwards > 0 ? max(0, $maxForwards - $forwardsCount - $forwardsReserved) : '—' }}</td>
                        <td class="uk-text-right">{{ $maxForwards ?: '—' }}</td>
                        <td class="uk-text-right uk-text-nowrap">
                            <a class="uk-icon-button" href="{{ route_w('client.mailforwards.preview') }}" uk-icon="icon: refresh" title="{{ __('ui.common.check_changes') }}"></a>
                            <form action="{{ route_w('client.mailforwards.sync') }}" method="POST" class="uk-display-inline">
                                @csrf
                                <button class="uk-icon-button" type="submit" uk-icon="icon: future" title="{{ __('ui.common.sync_now') }}"></button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ __('ui.client.resources.storage') }}</td>
                        <td class="uk-text-right">
                            {{ $fmtGb($usedWebspaceGb) }}
                            @if($usedWebspaceSource !== __('ui.client.none'))
                                <div class="uk-text-muted uk-text-small">{{ __('ui.common.source') }}: {{ $usedWebspaceSource }}</div>
                            @endif
                        </td>
                        <td class="uk-text-right">
                            {{ $fmtGb($usedReservedWebspaceGb) }}
                            @if($usedMailGb !== null || $usedHtdocsGb !== null || $usedDbGb !== null)
                                <div class="uk-text-muted uk-text-small">
                                    @if($usedMailGb !== null) {{ __('ui.client.resources.mail') }}: {{ $fmtGb($usedMailGb) }} @endif
                                    @if($usedHtdocsGb !== null) | {{ __('ui.client.resources.htdocs') }}: {{ $fmtGb($usedHtdocsGb) }} @endif
                                    @if($usedDbGb !== null) | {{ __('ui.client.resources.database') }}: {{ $fmtGb($usedDbGb) }} @endif
                                </div>
                            @endif
                        </td>
                        <td class="uk-text-right">{{ $maxWebspaceGb > 0 ? $fmtGb($freeWebspaceGb) : __('ui.client.none') }}</td>
                        <td class="uk-text-right">{{ $maxWebspaceGb > 0 ? $fmtGb($maxWebspaceGb) : __('ui.client.none') }}</td>
                        <td class="uk-text-right uk-text-nowrap">
                            <a class="uk-icon-button" href="{{ route_w('client.statistics.preview') }}" uk-icon="icon: refresh" title="{{ __('ui.common.check_changes') }}"></a>
                            <form action="{{ route_w('client.statistics.sync') }}" method="POST" class="uk-display-inline">
                                @csrf
                                <button class="uk-icon-button" type="submit" uk-icon="icon: future" title="{{ __('ui.common.sync_now') }}"></button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="uk-text-small uk-text-muted uk-margin-small-top">
            {{ __('ui.common.measurement') }}: {{ $lastSpaceReportAt ? \Carbon\Carbon::parse($lastSpaceReportAt)->format('d.m.Y H:i') : now()->format('d.m.Y H:i') }}
        </div>
    </div>

</div>
@endsection
