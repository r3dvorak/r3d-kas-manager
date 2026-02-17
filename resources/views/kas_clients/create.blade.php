{{-- 
    R3D KAS Manager – Create KAS Client
    @package   r3d-kas-manager
    @author    Richard Dvořák
    @version   0.14.8-alpha
    @date      2025-10-06
    @license   MIT License
--}}

@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1@m" style="max-width:600px;">
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

        <h2 class="uk-heading-line"><span>Neuen KAS Client anlegen</span></h2>

        @if ($errors->any())
            <div class="uk-alert-danger" uk-alert>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="kasClientForm" class="uk-form-stacked" method="POST" action="{{ route('kas-clients.store') }}">
            @csrf

            {{-- Beschreibung --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="account_comment">Beschreibung</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="account_comment" name="account_comment" type="text"
                        placeholder="z. B. 000 R3D" required>
                    <small class="uk-text-danger uk-hidden" id="error-account_comment">Bitte eine Beschreibung eingeben.</small>
                </div>
            </div>

            {{-- Login --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="account_login">Login</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="account_login" name="account_login" type="text"
                        placeholder="z. B. w01e77bc" required>
                    <small class="uk-text-danger uk-hidden" id="error-account_login">Bitte einen Login angeben.</small>
                </div>
            </div>

            {{-- Kontakt E-Mail --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="account_contact_mail">Kontakt E-Mail</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="account_contact_mail" name="account_contact_mail" type="email"
                        placeholder="z. B. faktura@domain.de">
                    <small class="uk-text-danger uk-hidden" id="error-account_contact_mail">Bitte eine gültige E-Mail eingeben.</small>
                </div>
            </div>

            <hr>
            <p class="uk-text-small uk-text-muted">
                Hinweis: Passwörter (KAS/API + App-Login) werden per CSV/Sync verwaltet, nicht in dieser Maske.
            </p>

            <hr>

            <h4 class="uk-margin-remove-top">Server-Metadaten (optional)</h4>

            <div class="uk-margin">
                <label class="uk-form-label" for="server_internal_domain">Interne Account-Domain</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="server_internal_domain" name="server_internal_domain" type="text"
                        placeholder="z. B. dd20724.srv">
                </div>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label" for="server_hostname">Server-Hostname</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="server_hostname" name="server_hostname" type="text"
                        placeholder="z. B. w0213ab8.kasserver.com">
                </div>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label" for="server_ip">Server-IP</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="server_ip" name="server_ip" type="text"
                        placeholder="z. B. 85.13.140.203">
                </div>
            </div>

            <hr>

            <h4 class="uk-margin-remove-top">Client-Menue</h4>
            <div class="uk-margin">
                <input type="hidden" name="client_menu_items_present" value="1">
                <div class="uk-grid-small uk-child-width-1-2@s" uk-grid>
                    @foreach($menuItems as $key)
                        <label><input class="uk-checkbox" type="checkbox" name="client_menu_items[]" value="{{ $key }}" checked> {{ $menuLabels[$key] ?? $key }}</label>
                    @endforeach
                </div>
                <p class="uk-text-small uk-text-muted uk-margin-small-top">Hier bestimmen Sie, welche Navigation der Klient sieht.</p>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label" for="preferred_locale">Voreingestellte Sprache</label>
                <div class="uk-form-controls">
                    <select class="uk-select" id="preferred_locale" name="preferred_locale">
                        <option value="de" selected>Deutsch</option>
                        <option value="en">English</option>
                    </select>
                </div>
            </div>

            <hr>

            <div class="uk-flex uk-flex-between">
                <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">← Abbrechen</a>
                <button type="submit" class="uk-button uk-button-primary">Speichern</button>
            </div>
        </form>

    </div>
</div>

{{-- Inline validation script --}}
<script>
document.getElementById('kasClientForm').addEventListener('submit', function (e) {

    console.log('Submitting form:', Object.fromEntries(new FormData(this)));
    
    let valid = true;

    const fields = ['account_comment', 'account_login', 'account_contact_mail'];

    fields.forEach(id => {
        const input = document.getElementById(id);
        const error = document.getElementById('error-' + id);
        input.classList.remove('uk-form-danger');
        error.classList.add('uk-hidden');
    });

    // Validate Beschreibung
    const comment = document.getElementById('account_comment');
    if (!comment.value.trim()) {
        showError('account_comment');
        valid = false;
    }

    // Validate Login
    const login = document.getElementById('account_login');
    if (!login.value.trim()) {
        showError('account_login');
        valid = false;
    }

    // Validate Email (if filled)
    const email = document.getElementById('account_contact_mail');
    if (email.value && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email.value)) {
        showError('account_contact_mail');
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
    }

    function showError(id) {
        const input = document.getElementById(id);
        const error = document.getElementById('error-' + id);
        input.classList.add('uk-form-danger');
        error.classList.remove('uk-hidden');
    }
});
</script>
@endsection
