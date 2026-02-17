@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Postfach anlegen</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('client.mailboxes.store') }}" method="POST" class="uk-form-stacked uk-card uk-card-default uk-card-body">
    @csrf

    <div class="uk-grid-small" uk-grid>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Domain</label>
            <select name="domain_id" class="uk-select" required>
                <option value="">Bitte waehlen</option>
                @foreach($domains as $domain)
                    <option value="{{ $domain->id }}" @selected(old('domain_id') == $domain->id)>{{ $domain->domain_full }}</option>
                @endforeach
            </select>
        </div>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Lokaler Teil (vor @)</label>
            <input class="uk-input" type="text" name="local_part" value="{{ old('local_part') }}" required>
        </div>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Mail-Login</label>
            <input class="uk-input" type="text" name="mail_login" value="{{ old('mail_login') }}" placeholder="optional">
        </div>
    </div>

    <div class="uk-grid-small uk-margin" uk-grid>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Status</label>
            <select name="status" class="uk-select">
                <option value="active" @selected(old('status', 'active') === 'active')>active</option>
                <option value="missing" @selected(old('status') === 'missing')>missing</option>
            </select>
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Postfach-Status</label>
            <select name="mailbox_access_state" class="uk-select">
                <option value="enabled" @selected(old('mailbox_access_state', 'enabled') === 'enabled')>aktiviert</option>
                <option value="receive_disabled" @selected(old('mailbox_access_state') === 'receive_disabled')>E-Mail-Empfang deaktiviert</option>
                <option value="blocked" @selected(old('mailbox_access_state') === 'blocked')>gesperrt</option>
            </select>
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Quota (MB)</label>
            <input class="uk-input" type="number" min="0" step="0.1" name="quota_mb" value="{{ old('quota_mb') }}">
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Verbrauch (KB)</label>
            <input class="uk-input" type="number" min="0" step="1" name="used_kb" value="{{ old('used_kb', 0) }}">
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Spamfilter</label>
            <input class="uk-input" type="text" name="spamfilter" value="{{ old('spamfilter') }}" placeholder="z.B. mark">
        </div>
    </div>

    <div class="uk-margin-top">
        <button type="submit" class="uk-button uk-button-primary">Speichern</button>
        <a href="{{ route('client.mailboxes.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </div>
</form>
@endsection
