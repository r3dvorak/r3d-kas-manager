@extends('layouts.app')

@section('content')
@php
    use App\Models\KasClient;
    use App\Models\KasDomain;
    use App\Models\KasSubdomain;
    use App\Models\KasMailAccount;
    use App\Models\KasMailForward;
    use App\Models\KasDatabase;
    use App\Models\KasFtpUser;

    $allClients = KasClient::query()->get();

    $master = $allClients->first(function ($c) {
        return str_starts_with(strtoupper((string)($c->account_comment ?? '')), '000 MASTER');
    });

    if (!$master) {
        $master = $allClients->firstWhere('account_login', 'w0213ab8') ?? $allClients->first();
    }

    $children = $master ? $allClients->where('id', '!=', $master->id) : collect();

    $maxToLabel = function ($value, bool $asGb = false): string {
        $n = (float)($value ?? 0);
        if ($n <= 0) return 'unbegrenzt';
        if ($asGb) return number_format(round($n / 1024, 2), 2, ',', '.') . ' GB';
        return number_format((int)$n, 0, ',', '.');
    };

    $remainingToLabel = function ($possible, $used, bool $asGb = false): string {
        $p = (float)($possible ?? 0);
        if ($p <= 0) return 'unbegrenzt';
        $r = max(0, $p - (float)$used);
        if ($asGb) return number_format(round($r / 1024, 2), 2, ',', '.') . ' GB';
        return number_format((int)$r, 0, ',', '.');
    };

    $serverHostname = (string)($master?->server_hostname ?? '—');
    $serverIp = (string)($master?->server_ip ?? '—');
    $rootPath = $master ? '/www/htdocs/' . $master->account_login . '/' : '—';

    $accountsCreated = (int)$children->count();
    $accountsReserved = (int)$children->sum('max_account');
    $accountsPossible = (int)($master?->max_account ?? 0);
    if ($accountsPossible <= 0) {
        $accountsPossible = 500;
    }

    // "angelegt" should reflect total current inventory in our DB snapshots (not only master account itself).
    $domainsCreated = KasDomain::whereNull('deleted_at')->count();
    $domainsReserved = (int)$children->sum('max_domain');
    $domainsPossible = (int)($master?->max_domain ?? 0);

    $subdomainsCreated = KasSubdomain::whereNull('deleted_at')->count();
    $subdomainsReserved = (int)$children->sum('max_subdomain');
    $subdomainsPossible = (int)($master?->max_subdomain ?? 0);

    $mailboxesCreated = KasMailAccount::where('status', 'active')->count();
    $mailboxesReserved = (int)$children->sum('max_mail_account');
    $mailboxesPossible = (int)($master?->max_mail_account ?? 0);

    $forwardsCreated = KasMailForward::where('status', 'active')->count();
    $forwardsReserved = (int)$children->sum('max_mail_forward');
    $forwardsPossible = (int)($master?->max_mail_forward ?? 0);

    $dbCreated = KasDatabase::where('status', 'active')->count();
    $dbReserved = (int)$children->sum('max_databases');
    $dbPossible = (int)($master?->max_databases ?? 0);

    $ftpCreated = KasFtpUser::where('status', 'active')->count();
    $ftpReserved = (int)$children->sum('max_ftpuser');
    $ftpPossible = (int)($master?->max_ftpuser ?? 0);

    $spaceCreatedMb = (float)$allClients->sum('used_account_space');
    $spaceReservedMb = (float)$children->sum('max_webspace');
    $spacePossibleMb = (float)($master?->max_webspace ?? 0);
@endphp

<div class="uk-container">
    <h1 class="uk-heading-line"><span>Willkommen in der technischen Verwaltung</span></h1>
    <p class="uk-text-muted uk-margin-remove-top">
        Wichtiger Hinweis: Einstellungen werden nicht sofort auf dem Server umgesetzt. Es kann einige Minuten dauern, bis Aenderungen wirksam werden.
    </p>

    <div class="uk-alert-primary" uk-alert>
        <p class="uk-margin-remove">
            <strong>Hauptkonto:</strong> {{ $master?->account_login ?? '—' }}
            <span class="uk-text-muted">|</span>
            <strong>Beschreibung:</strong> {{ $master?->account_comment ?? '—' }}
        </p>
    </div>

    <div class="uk-card uk-card-default uk-card-body uk-margin">
        <div class="uk-grid-small uk-child-width-1-3@m uk-margin-top" uk-grid>
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-padding-small">
                    <div class="uk-text-small uk-text-muted">aktuelle Server-IP</div>
                    <div class="uk-text-bold">{{ $serverIp }}</div>
                </div>
            </div>
            <div>
                <div class="uk-card uk-card-default uk-card-body uk-padding-small">
                    <div class="uk-text-small uk-text-muted">Servername</div>
                    <div class="uk-text-bold">{{ $serverHostname }}</div>
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
                    <th class="uk-text-right">angelegt</th>
                    <th class="uk-text-right">reserviert</th>
                    <th class="uk-text-right">verbleibend</th>
                    <th class="uk-text-right">moeglich</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Accounts</td>
                    <td class="uk-text-right">{{ number_format($accountsCreated, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ number_format($accountsReserved, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ $remainingToLabel($accountsPossible, $accountsCreated + $accountsReserved) }}</td>
                    <td class="uk-text-right">{{ $maxToLabel($accountsPossible) }}</td>
                </tr>
                <tr>
                    <td>Speicherplatz</td>
                    <td class="uk-text-right">{{ number_format(round($spaceCreatedMb / 1024, 2), 2, ',', '.') }} GB</td>
                    <td class="uk-text-right">{{ number_format(round($spaceReservedMb / 1024, 2), 2, ',', '.') }} GB</td>
                    <td class="uk-text-right">{{ $remainingToLabel($spacePossibleMb, $spaceReservedMb, true) }}</td>
                    <td class="uk-text-right">{{ $maxToLabel($spacePossibleMb, true) }}</td>
                </tr>
                <tr>
                    <td>Domain</td>
                    <td class="uk-text-right">{{ number_format($domainsCreated, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ number_format($domainsReserved, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ $remainingToLabel($domainsPossible, $domainsCreated + $domainsReserved) }}</td>
                    <td class="uk-text-right">{{ $maxToLabel($domainsPossible) }}</td>
                </tr>
                <tr>
                    <td>Subdomains</td>
                    <td class="uk-text-right">{{ number_format($subdomainsCreated, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ number_format($subdomainsReserved, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ $remainingToLabel($subdomainsPossible, $subdomainsCreated + $subdomainsReserved) }}</td>
                    <td class="uk-text-right">{{ $maxToLabel($subdomainsPossible) }}</td>
                </tr>
                <tr>
                    <td>E-Mail-Postfaecher</td>
                    <td class="uk-text-right">{{ number_format($mailboxesCreated, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ number_format($mailboxesReserved, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ $remainingToLabel($mailboxesPossible, $mailboxesCreated + $mailboxesReserved) }}</td>
                    <td class="uk-text-right">{{ $maxToLabel($mailboxesPossible) }}</td>
                </tr>
                <tr>
                    <td>E-Mail-Weiterleitungen</td>
                    <td class="uk-text-right">{{ number_format($forwardsCreated, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ number_format($forwardsReserved, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ $remainingToLabel($forwardsPossible, $forwardsCreated + $forwardsReserved) }}</td>
                    <td class="uk-text-right">{{ $maxToLabel($forwardsPossible) }}</td>
                </tr>
                <tr>
                    <td>Datenbanken</td>
                    <td class="uk-text-right">{{ number_format($dbCreated, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ number_format($dbReserved, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ $remainingToLabel($dbPossible, $dbCreated + $dbReserved) }}</td>
                    <td class="uk-text-right">{{ $maxToLabel($dbPossible) }}</td>
                </tr>
                <tr>
                    <td>FTP-Nutzer (zusaetzlich)</td>
                    <td class="uk-text-right">{{ number_format($ftpCreated, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ number_format($ftpReserved, 0, ',', '.') }}</td>
                    <td class="uk-text-right">{{ $remainingToLabel($ftpPossible, $ftpCreated + $ftpReserved) }}</td>
                    <td class="uk-text-right">{{ $maxToLabel($ftpPossible) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
