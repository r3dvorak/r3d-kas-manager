@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Recipe Wizard (Admin)</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route_w('admin.recipes.wizard.store') }}" method="POST" class="uk-card uk-card-default uk-card-body">
    @csrf
    <div class="uk-grid-small" uk-grid>
        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Recipe Name</label>
            <input class="uk-input" name="name" value="{{ old('name') }}" placeholder="Onboarding Kunde X" required>
        </div>
        <div class="uk-width-1-2@m">
            <label class="uk-form-label">PHP Default</label>
            <input class="uk-input" name="php_version" value="{{ old('php_version', '8.3') }}" placeholder="8.3" required>
        </div>

        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Hauptdomain</label>
            <input class="uk-input" name="main_domain" value="{{ old('main_domain') }}" placeholder="example.tld" required>
        </div>
        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Zusätzliche Domains</label>
            <input class="uk-input" name="extra_domains" value="{{ old('extra_domains') }}" placeholder="www.example.tld, shop.example.tld">
        </div>

        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Mailbox Prefixe</label>
            <input class="uk-input" name="mailbox_prefixes" value="{{ old('mailbox_prefixes') }}" placeholder="info, kontakt, support">
        </div>
        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Mailbox Quota (MB)</label>
            <input type="number" min="0" class="uk-input" name="mail_quota_mb" value="{{ old('mail_quota_mb', 2048) }}">
        </div>

        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Forward Prefixe</label>
            <input class="uk-input" name="forward_prefixes" value="{{ old('forward_prefixes') }}" placeholder="jobs, booking">
        </div>
        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Forward Zieladresse</label>
            <input class="uk-input" name="forward_target" value="{{ old('forward_target') }}" placeholder="ziel@example.tld">
        </div>

        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Anzahl Datenbanken</label>
            <input type="number" min="0" class="uk-input" name="database_count" value="{{ old('database_count', 0) }}">
        </div>
        <div class="uk-width-1-2@m uk-flex uk-flex-middle">
            <label><input class="uk-checkbox" type="checkbox" name="enable_ssl" value="1" {{ old('enable_ssl') ? 'checked' : '' }}> SSL als Policy aktivieren</label>
        </div>

        <div class="uk-width-1-1">
            <label class="uk-form-label">Beschreibung</label>
            <textarea class="uk-textarea" rows="3" name="description">{{ old('description') }}</textarea>
        </div>
    </div>

    <div class="uk-margin-top">
        <button class="uk-button uk-button-primary" type="submit">Recipe generieren</button>
        <a href="{{ route_w('admin.recipes.index') }}" class="uk-button uk-button-default">Zurück</a>
    </div>
</form>
@endsection

