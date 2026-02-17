@extends('layouts.app')

@section('content')
    @php
        $menuItems = \App\Models\KasClient::clientNavKeys();
        $menuLabels = [
            'dashboard' => 'Startseite',
            'domain' => 'Domain',
            'subdomain' => 'Subdomain',
            'mailboxes' => 'Mailkonten',
            'mailforwards' => 'Weiterleitungen',
            'ftp' => 'FTP',
            'databases' => 'Datenbanken',
            'dns' => 'DNS',
            'ssl' => 'SSL-Schutz',
            'statistics' => 'Statistik',
            'recipes' => 'Rezepte',
        ];
    @endphp
    <h1 class="uk-heading-line"><span>KAS Client bearbeiten</span></h1>

    <form class="uk-form-stacked" action="{{ route('kas-clients.update', $kasClient) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="uk-margin">
            <label class="uk-form-label">Beschreibung</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="account_comment" value="{{ $kasClient->account_comment }}" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Login</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" value="{{ $kasClient->account_login }}" readonly>
                <small class="uk-text-muted">Login wird per CSV/Sync verwaltet.</small>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Kontakt E-Mail</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="email" name="account_contact_mail" value="{{ $kasClient->account_contact_mail }}">
            </div>
        </div>

        <hr>

        <h4 class="uk-margin-remove-top">Server-Metadaten (optional)</h4>

        <div class="uk-margin">
            <label class="uk-form-label">Interne Account-Domain</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="server_internal_domain" value="{{ $kasClient->server_internal_domain }}" placeholder="z. B. dd20724.srv">
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Server-Hostname</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="server_hostname" value="{{ $kasClient->server_hostname }}" placeholder="z. B. w0213ab8.kasserver.com">
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Server-IP</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="server_ip" value="{{ $kasClient->server_ip }}" placeholder="z. B. 85.13.140.203">
            </div>
        </div>

        <hr>
        <h4 class="uk-margin-remove-top">Client-Menue</h4>
        <div class="uk-margin">
            <input type="hidden" name="client_menu_items_present" value="1">
            <div class="uk-grid-small uk-child-width-1-2@s" uk-grid>
                @foreach($menuItems as $key)
                    <label><input class="uk-checkbox" type="checkbox" name="client_menu_items[]" value="{{ $key }}" {{ $kasClient->hasClientMenuItem($key) ? 'checked' : '' }}> {{ $menuLabels[$key] ?? $key }}</label>
                @endforeach
            </div>
            <p class="uk-text-small uk-text-muted uk-margin-small-top">Hier bestimmen Sie, welche Navigation der Klient sieht.</p>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Voreingestellte Sprache</label>
            <div class="uk-form-controls">
                <select class="uk-select" name="preferred_locale">
                    <option value="de" {{ ($kasClient->preferred_locale ?? 'de') === 'de' ? 'selected' : '' }}>Deutsch</option>
                    <option value="en" {{ ($kasClient->preferred_locale ?? 'de') === 'en' ? 'selected' : '' }}>English</option>
                </select>
            </div>
        </div>

        <button type="submit" class="uk-button uk-button-primary">Aktualisieren</button>
        <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </form>
@endsection
