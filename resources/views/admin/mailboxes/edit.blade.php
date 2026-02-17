@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Mailbox bearbeiten (Admin)</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.mailboxes.update', $mailbox) }}" method="POST" class="uk-form-stacked uk-card uk-card-default uk-card-body">
    @csrf
    @method('PUT')

    <div class="uk-grid-small" uk-grid>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">KAS Login</label>
            <select name="kas_login" class="uk-select" required>
                @foreach($clients as $c)
                    <option value="{{ $c->account_login }}" @selected(old('kas_login', $mailbox->kas_login) === $c->account_login)>
                        {{ $c->account_comment }} ({{ $c->account_login }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Domain</label>
            <input class="uk-input" type="text" name="domain" value="{{ old('domain', $mailbox->domain) }}" required>
        </div>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Lokaler Teil (vor @)</label>
            <input class="uk-input" type="text" name="local_part" value="{{ old('local_part', $localPart) }}" required>
        </div>
    </div>

    <div class="uk-grid-small uk-margin" uk-grid>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Mail-Login</label>
            <input class="uk-input" type="text" name="mail_login" value="{{ old('mail_login', $mailbox->mail_login) }}">
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Status</label>
            <select name="status" class="uk-select">
                <option value="active" @selected(old('status', $mailbox->status) === 'active')>active</option>
                <option value="missing" @selected(old('status', $mailbox->status) === 'missing')>missing</option>
            </select>
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Postfach-Status</label>
            <select name="mailbox_access_state" class="uk-select">
                <option value="enabled" @selected(old('mailbox_access_state', $mailbox->mailboxAccessState()) === 'enabled')>aktiviert</option>
                <option value="receive_disabled" @selected(old('mailbox_access_state', $mailbox->mailboxAccessState()) === 'receive_disabled')>E-Mail-Empfang deaktiviert</option>
                <option value="blocked" @selected(old('mailbox_access_state', $mailbox->mailboxAccessState()) === 'blocked')>gesperrt</option>
            </select>
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Quota (MB)</label>
            <input class="uk-input" type="number" min="0" step="0.1" name="quota_mb" value="{{ old('quota_mb') }}">
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Verbrauch (KB)</label>
            <input class="uk-input" type="number" min="0" step="1" name="used_kb" value="{{ old('used_kb', (string) ($mailbox->data_json['used_mailaccount_space'] ?? 0)) }}">
        </div>
    </div>

    <div class="uk-margin uk-width-1-4@m">
        <label class="uk-form-label">Spamfilter</label>
        <input class="uk-input" type="text" name="spamfilter" value="{{ old('spamfilter', (string) ($mailbox->data_json['mail_spamfilter'] ?? '')) }}">
    </div>

    <div class="uk-margin-top">
        <button type="submit" class="uk-button uk-button-primary">Aktualisieren</button>
        <a href="{{ route('admin.mailboxes.index', ['kas_login' => $mailbox->kas_login]) }}" class="uk-button uk-button-default">Zurueck</a>
    </div>
</form>
@endsection
