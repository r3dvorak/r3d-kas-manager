@extends('layouts.app')

@section('content')
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

        <button type="submit" class="uk-button uk-button-primary">Aktualisieren</button>
        <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </form>
@endsection
