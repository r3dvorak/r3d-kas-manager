@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Postfach bearbeiten</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('client.mailboxes.update', $mailbox) }}" method="POST" class="uk-form-stacked uk-card uk-card-default uk-card-body">
    @csrf
    @method('PUT')

    <div class="uk-grid-small" uk-grid>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Domain</label>
            <select name="domain_id" class="uk-select" required>
                @foreach($domains as $domain)
                    <option value="{{ $domain->id }}" @selected((int) old('domain_id', $mailbox->domain_id) === (int) $domain->id)>{{ $domain->domain_full }}</option>
                @endforeach
            </select>
        </div>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Lokaler Teil (vor @)</label>
            <input class="uk-input" type="text" name="local_part" value="{{ old('local_part', $localPart) }}" required>
        </div>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Mail-Login</label>
            <input class="uk-input" type="text" name="mail_login" value="{{ old('mail_login', (string) $mailbox->mail_login) }}">
        </div>
    </div>

    <div class="uk-grid-small uk-margin" uk-grid>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Status</label>
            <select name="status" class="uk-select">
                <option value="active" @selected(old('status', (string) $mailbox->status) === 'active')>active</option>
                <option value="missing" @selected(old('status', (string) $mailbox->status) === 'missing')>missing</option>
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
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Spamfilter</label>
            <input class="uk-input" type="text" name="spamfilter" value="{{ old('spamfilter', (string) ($mailbox->data_json['mail_spamfilter'] ?? '')) }}">
        </div>
    </div>

    <div class="uk-margin-top">
        <button type="submit" class="uk-button uk-button-primary">Aktualisieren</button>
        <a href="{{ route('client.mailboxes.index') }}" class="uk-button uk-button-default">Zurueck</a>
    </div>
</form>
@endsection
