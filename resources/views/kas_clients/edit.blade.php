@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>KAS Client bearbeiten</span></h1>

    <form class="uk-form-stacked" action="{{ route('kas-clients.update', $kasClient) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="uk-margin">
            <label class="uk-form-label">Name</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="name" value="{{ $kasClient->name }}" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Login</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="login" value="{{ $kasClient->login }}" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Domain</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="domain" value="{{ $kasClient->domain }}" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">API Passwort (leer lassen = unverändert)</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="password" name="api_password">
            </div>
        </div>

        <button type="submit" class="uk-button uk-button-primary">Aktualisieren</button>
        <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </form>
@endsection
