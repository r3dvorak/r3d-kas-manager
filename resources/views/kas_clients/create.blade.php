@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>KAS Client anlegen</span></h1>

    <form class="uk-form-stacked" action="{{ route('kas-clients.store') }}" method="POST">
        @csrf

        <div class="uk-margin">
            <label class="uk-form-label">Name</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="name" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Login</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="login" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Domain</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="domain" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">API Passwort</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="password" name="api_password" required>
            </div>
        </div>

        <button type="submit" class="uk-button uk-button-primary">Speichern</button>
        <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </form>
@endsection
