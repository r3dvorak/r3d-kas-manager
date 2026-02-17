@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Weiterleitung bearbeiten (Admin)</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.mailforwards.update', $forward) }}" method="POST" class="uk-form-stacked uk-card uk-card-default uk-card-body">
    @csrf
    @method('PUT')

    <div class="uk-grid-small" uk-grid>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">KAS Login</label>
            <select name="kas_login" class="uk-select" required>
                @foreach($clients as $c)
                    <option value="{{ $c->account_login }}" @selected(old('kas_login', $forward->kas_login) === $c->account_login)>
                        {{ $c->account_comment }} ({{ $c->account_login }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Von (Adresse)</label>
            <input class="uk-input" type="text" name="mail_forward_address" value="{{ old('mail_forward_address', $forward->mail_forward_address) }}" required>
        </div>
        <div class="uk-width-1-3@m">
            <label class="uk-form-label">Status</label>
            <select name="status" class="uk-select">
                <option value="active" @selected(old('status', $forward->status) === 'active')>active</option>
                <option value="missing" @selected(old('status', $forward->status) === 'missing')>missing</option>
            </select>
        </div>
    </div>

    <div class="uk-margin">
        <label class="uk-form-label">Ziele (kommagetrennt)</label>
        <textarea class="uk-textarea" name="mail_forward_targets" rows="3" required>{{ old('mail_forward_targets', $forward->mail_forward_targets) }}</textarea>
    </div>

    <div class="uk-grid-small" uk-grid>
        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Kommentar</label>
            <input class="uk-input" type="text" name="mail_forward_comment" value="{{ old('mail_forward_comment', $forward->mail_forward_comment) }}">
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Spamfilter</label>
            <input class="uk-input" type="text" name="mail_forward_spamfilter" value="{{ old('mail_forward_spamfilter', $forward->mail_forward_spamfilter) }}">
        </div>
        <div class="uk-width-1-4@m uk-flex uk-flex-middle">
            <label><input class="uk-checkbox" type="checkbox" name="in_progress" value="1" @checked(old('in_progress', (bool) $forward->in_progress))> in progress</label>
        </div>
    </div>

    <div class="uk-margin-top">
        <button type="submit" class="uk-button uk-button-primary">Aktualisieren</button>
        <a href="{{ route('admin.mailforwards.index', ['kas_login' => $forward->kas_login]) }}" class="uk-button uk-button-default">Zurueck</a>
    </div>
</form>
@endsection
