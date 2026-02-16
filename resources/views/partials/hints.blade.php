@php
    use App\Models\AppSetting;
    use Illuminate\Support\Facades\Auth;

    $showPublicIp   = (string) AppSetting::getValue('hints_show_public_ip', '1') === '1';
    $showAccount    = (string) AppSetting::getValue('hints_show_account_data', '1') === '1';
    $showEnv        = (string) AppSetting::getValue('hints_show_env_versions', '1') === '1';
    $showReports    = (string) AppSetting::getValue('hints_show_last_reports', '1') === '1';
    $showKasStatus  = (string) AppSetting::getValue('hints_show_kas_status', '1') === '1';
    $showQuickLinks = (string) AppSetting::getValue('hints_show_quick_links', '1') === '1';
    $showContextHelp = (string) AppSetting::getValue('hints_show_context_help', '1') === '1';

    $isAdmin = Auth::guard('web')->check();
    $isClient = Auth::guard('kas_client')->check();
    $isAuthenticated = $isAdmin || $isClient;

    $customer = (string) AppSetting::getValue('all_inkl_customer_number', '');
    $contract = (string) AppSetting::getValue('all_inkl_contract_number', '');
    $membersLogin = (string) AppSetting::getValue('all_inkl_members_login', $customer);
    $monitorLogin = (string) AppSetting::getValue('all_inkl_monitor_login', '');

    $kasLastErrorAt = (string) AppSetting::getValue('kas_last_error_at', '');
    $kasLastErrorAction = (string) AppSetting::getValue('kas_last_error_action', '');
    $kasLastErrorMessage = (string) AppSetting::getValue('kas_last_error_message', '');
    $kasLastSuccessAt = (string) AppSetting::getValue('kas_last_success_at', '');

    $auditCsv = storage_path('kas_responses/accounts-audit-report.csv');
    $sanJson  = storage_path('kas_responses/get_accounts.sanitized.json');

    $fmtTs = function (?int $ts): string {
        if (!$ts) return '—';
        return date('Y-m-d H:i:s', $ts);
    };

    $auditTs = is_file($auditCsv) ? @filemtime($auditCsv) : null;
    $sanTs   = is_file($sanJson) ? @filemtime($sanJson) : null;
@endphp

<div class="uk-text-small">
    @if($showPublicIp)
        <div class="uk-margin-small-bottom">
            <strong>Aktuelle oeffentliche IP</strong><br>
            <span class="uk-text-muted">{{ app(\App\Services\PublicIpService::class)->getPublicIp() }}</span>
        </div>
        <hr class="uk-margin-small">
    @endif

    @if($isAdmin && $showAccount)
        <div class="uk-margin-small-bottom">
            <strong>Kontodaten</strong>
            <div class="uk-text-muted">Kundennummer: {{ $customer !== '' ? $customer : '—' }}</div>
            <div class="uk-text-muted">Vertragsnummer: {{ $contract !== '' ? $contract : '—' }}</div>
        </div>
    @endif

    @if($isAdmin && $showQuickLinks)
        <ul class="uk-list uk-list-divider uk-margin-remove">
            <li>
                <a href="https://all-inkl.com/members" target="_blank" rel="noopener noreferrer">Members</a><br>
                <span class="uk-text-muted">Login: {{ $membersLogin !== '' ? $membersLogin : '—' }}</span>
            </li>
            <li>
                <a href="https://all-inkl.com/monitor" target="_blank" rel="noopener noreferrer">Monitor</a><br>
                <span class="uk-text-muted">Login: {{ $monitorLogin !== '' ? $monitorLogin : '—' }}</span>
            </li>
            <li>
                <a href="https://kasapi.kasserver.com/dokumentation/" target="_blank" rel="noopener noreferrer">KAS API Doku</a>
            </li>
        </ul>
    @endif

    @if($isAdmin && ($showAccount || $showQuickLinks))
        <hr class="uk-margin-small">
    @endif

    @if($showEnv && $isAuthenticated)
        <div class="uk-margin-small-bottom">
            <strong>Environment / Versionen</strong>
            <div class="uk-text-muted">APP_ENV: {{ config('app.env') }}</div>
            <div class="uk-text-muted">APP_URL: {{ config('app.url') }}</div>
            <div class="uk-text-muted">Laravel: {{ app()->version() }}</div>
            <div class="uk-text-muted">PHP: {{ PHP_VERSION }}</div>
        </div>
        <hr class="uk-margin-small">
    @endif

    @if($isAdmin && $showReports)
        <div class="uk-margin-small-bottom">
            <strong>Letzte Reports</strong>
            <div class="uk-text-muted">accounts-audit-report.csv: {{ $fmtTs($auditTs) }}</div>
            <div class="uk-text-muted">get_accounts.sanitized.json: {{ $fmtTs($sanTs) }}</div>
        </div>
        <hr class="uk-margin-small">
    @endif

    @if($isAdmin && $showKasStatus)
        <div class="uk-margin-small-bottom">
            <strong>KAS API Status</strong>
            @if($kasLastErrorAt !== '')
                <div class="uk-text-danger">Letzter Fehler: {{ $kasLastErrorAt }}</div>
                @if($kasLastErrorAction !== '')
                    <div class="uk-text-muted">Action: {{ $kasLastErrorAction }}</div>
                @endif
                @if($kasLastErrorMessage !== '')
                    <div class="uk-text-muted">{{ $kasLastErrorMessage }}</div>
                @endif
            @elseif($kasLastSuccessAt !== '')
                <div class="uk-text-success">Letzter Erfolg: {{ $kasLastSuccessAt }}</div>
            @else
                <div class="uk-text-muted">—</div>
            @endif
        </div>
    @endif

    @if($showContextHelp)
        @if($isAdmin && ($showReports || $showKasStatus))
            <hr class="uk-margin-small">
        @endif
        <div class="uk-margin-small-bottom">
            <strong>Kontext Hilfe</strong>
            <div class="uk-text-muted">Geben Sie Ihren Admin-Login oder Ihre Domain ein – die Anmeldung erkennt automatisch den Typ.</div>
        </div>
    @endif
</div>
