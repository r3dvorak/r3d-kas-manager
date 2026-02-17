{{-- 
    R3D KAS Manager
    @package   r3d-kas-manager
    @author    Richard Dvořák
    @version   0.28.22-alpha
    @date      2025-09-26
    @license   MIT License
--}}
@extends('layouts.app')

@section('content')
<div class="uk-width-2-3@m">
    <h2 class="uk-heading-line"><span>Systemeinstellungen</span></h2>

    @if(session('success'))
        <div class="uk-alert-success" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <form action="{{ route('config.update') }}" method="POST" enctype="multipart/form-data" class="uk-form-stacked uk-margin-large-top">
        @csrf

        {{-- Sicherheit --}}
        <fieldset class="uk-fieldset">
            <legend class="uk-legend">Sicherheit</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Session-Timeout (Minuten)</label>
                <input class="uk-input" type="number" name="session_timeout"
                       value="{{ $settings['session_timeout'] ?? 30 }}" min="5" max="240">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Absolute Sitzungslänge (Minuten)</label>
                <input class="uk-input" type="number" name="absolute_session_max"
                       value="{{ $settings['absolute_session_max'] ?? 180 }}" min="30" max="1440">
            </div>
        </fieldset>

        {{-- System --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">System</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Support-E-Mail</label>
                <input class="uk-input" type="email" name="support_email"
                       value="{{ $settings['support_email'] ?? 'info@r3d.de' }}">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Seitentitel / Name</label>
                <input class="uk-input" type="text" name="site_name"
                       value="{{ $settings['site_name'] ?? 'R3D KAS Manager' }}">
            </div>
        </fieldset>

        {{-- Workspaces / Sessions --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">Workspaces / Sessions</legend>

            @php
                $wsEnabled = (string) ($settings['workspace_isolation_enabled'] ?? '1') === '1';
            @endphp

            <div class="uk-margin">
                <label>
                    <input class="uk-checkbox" type="checkbox" name="workspace_isolation_enabled" {{ $wsEnabled ? 'checked' : '' }}>
                    Workspace-Isolation aktiv (Tab-getrennte Sessions)
                </label>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Max. parallele Workspaces pro User</label>
                <input class="uk-input" type="number" name="workspace_max_active"
                       value="{{ $settings['workspace_max_active'] ?? 10 }}" min="1" max="100">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Limit-Strategie bei vollem Workspace-Kontingent</label>
                <select class="uk-select" name="workspace_limit_strategy">
                    <option value="reuse_existing" {{ ($settings['workspace_limit_strategy'] ?? 'reuse_existing') === 'reuse_existing' ? 'selected' : '' }}>Neuen Tab auf bestehenden Workspace umleiten</option>
                    <option value="allow_new" {{ ($settings['workspace_limit_strategy'] ?? 'reuse_existing') === 'allow_new' ? 'selected' : '' }}>Neuen Workspace trotzdem erlauben</option>
                </select>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Workspace Idle Timeout (Minuten)</label>
                <input class="uk-input" type="number" name="workspace_idle_timeout_minutes"
                       value="{{ $settings['workspace_idle_timeout_minutes'] ?? 300 }}" min="5" max="1440">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Workspace Absolute Lifetime (Stunden)</label>
                <input class="uk-input" type="number" name="workspace_absolute_lifetime_hours"
                       value="{{ $settings['workspace_absolute_lifetime_hours'] ?? 24 }}" min="1" max="720">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Impersonation-Workspace-Puffer</label>
                <input class="uk-input" type="number" name="impersonation_workspace_buffer"
                       value="{{ $settings['impersonation_workspace_buffer'] ?? 2 }}" min="0" max="20">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Logout-Standardmodus</label>
                <select class="uk-select" name="logout_scope_default">
                    <option value="current" {{ ($settings['logout_scope_default'] ?? 'current') === 'current' ? 'selected' : '' }}>Nur aktueller Workspace</option>
                    <option value="all" {{ ($settings['logout_scope_default'] ?? 'current') === 'all' ? 'selected' : '' }}>Alle Workspaces im Browser</option>
                </select>
            </div>
        </fieldset>

        {{-- Externe Launches --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">Externe Launches (Webmail / phpMyAdmin)</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Launch-Token TTL (Sekunden)</label>
                <input class="uk-input" type="number" name="external_launch_token_ttl"
                       value="{{ $settings['external_launch_token_ttl'] ?? 60 }}" min="15" max="900">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Audit-Log-Level</label>
                <select class="uk-select" name="audit_log_level">
                    <option value="minimal" {{ ($settings['audit_log_level'] ?? 'standard') === 'minimal' ? 'selected' : '' }}>Minimal</option>
                    <option value="standard" {{ ($settings['audit_log_level'] ?? 'standard') === 'standard' ? 'selected' : '' }}>Standard</option>
                    <option value="verbose" {{ ($settings['audit_log_level'] ?? 'standard') === 'verbose' ? 'selected' : '' }}>Verbose</option>
                </select>
            </div>
        </fieldset>

        {{-- all-inkl --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">all-inkl</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Kundennummer</label>
                <input class="uk-input" type="text" name="all_inkl_customer_number"
                       value="{{ $settings['all_inkl_customer_number'] ?? '' }}" placeholder="z. B. 858656">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Vertragsnummer</label>
                <input class="uk-input" type="text" name="all_inkl_contract_number"
                       value="{{ $settings['all_inkl_contract_number'] ?? '' }}" placeholder="z. B. 1822536">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Members Login</label>
                <input class="uk-input" type="text" name="all_inkl_members_login"
                       value="{{ $settings['all_inkl_members_login'] ?? '' }}" placeholder="z. B. 858656">
                <div class="uk-text-meta">Hinweis: ist oft identisch mit der Kundennummer.</div>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Monitor Login</label>
                <input class="uk-input" type="text" name="all_inkl_monitor_login"
                       value="{{ $settings['all_inkl_monitor_login'] ?? '' }}" placeholder="z. B. dd20724.srv">
            </div>
        </fieldset>

        {{-- Hinweise --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">Hinweise (Sidebar)</legend>

            @php
                $h = fn(string $k, string $d = '1') => (string)($settings[$k] ?? $d) === '1';
            @endphp

            <div class="uk-margin">
                <label><input class="uk-checkbox" type="checkbox" name="hints_show_public_ip" {{ $h('hints_show_public_ip','1') ? 'checked' : '' }}> Aktuelle oeffentliche IP</label>
            </div>
            <div class="uk-margin">
                <label><input class="uk-checkbox" type="checkbox" name="hints_show_account_data" {{ $h('hints_show_account_data','1') ? 'checked' : '' }}> Kontodaten (Kundennummer/Vertrag)</label>
            </div>
            <div class="uk-margin">
                <label><input class="uk-checkbox" type="checkbox" name="hints_show_quick_links" {{ $h('hints_show_quick_links','1') ? 'checked' : '' }}> Links (Members/Monitor/KAS API Doku)</label>
            </div>
            <div class="uk-margin">
                <label><input class="uk-checkbox" type="checkbox" name="hints_show_env_versions" {{ $h('hints_show_env_versions','1') ? 'checked' : '' }}> Environment / Versionen</label>
            </div>
            <div class="uk-margin">
                <label><input class="uk-checkbox" type="checkbox" name="hints_show_last_reports" {{ $h('hints_show_last_reports','1') ? 'checked' : '' }}> Letzte Reports (audit/sanitized)</label>
            </div>
            <div class="uk-margin">
                <label><input class="uk-checkbox" type="checkbox" name="hints_show_kas_status" {{ $h('hints_show_kas_status','1') ? 'checked' : '' }}> KAS API Status (letzter Fehler/Erfolg)</label>
            </div>
            <div class="uk-margin">
                <label><input class="uk-checkbox" type="checkbox" name="hints_show_context_help" {{ $h('hints_show_context_help','1') ? 'checked' : '' }}> Kontext Hilfe</label>
                <div class="uk-text-meta">Hinweis: Admin-only Bereiche (Kontodaten/Links/Reports/Status) werden fuer Klienten trotzdem nicht angezeigt.</div>
            </div>
        </fieldset>

        {{-- Mail --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">Mail</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Standardfilter (Spam/Virus) fuer neue Mailboxen</label>
                <input class="uk-input" type="text" name="mail_standardfilter_default"
                       value="{{ $settings['mail_standardfilter_default'] ?? 'rspamd;pdw;virus_mark;scbl:mark;brbl:mark' }}">
                <div class="uk-text-meta">
                    Format: <code>filter1;filter2:action;...</code> (z.B. <code>scbl:mark</code>). Dieser Wert wird von <code>kas:apply-mailstandardfilter-from-csv</code> verwendet.
                </div>
            </div>
        </fieldset>

        {{-- Branding / Logo --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">Branding</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Logo-Upload (SVG, PNG, JPG, GIF max 1 MB)</label>
                <input class="uk-input" type="file" name="logo_file" accept=".svg,.png,.jpg,.jpeg,.gif">
            </div>

            @if(!empty($settings['logo_url']))
                <div class="uk-margin-small-top uk-text-center">
                    <img src="{{ $settings['logo_url'] }}" alt="Current Logo" style="max-height:80px;">
                    <div class="uk-text-meta">{{ $settings['logo_url'] }}</div>
                </div>
            @endif
        </fieldset>

        <button type="submit" class="uk-button uk-button-primary uk-margin-top">Speichern</button>
    </form>
</div>
@endsection
