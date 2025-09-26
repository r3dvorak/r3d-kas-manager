@extends('layouts.app')

@section('content')
<div class="uk-container uk-margin-large-top uk-width-1-3@m">
    <div class="uk-card uk-card-default uk-card-body">
        <h2 class="uk-card-title">KAS Client Login</h2>
        <form method="POST" action="{{ route('kas-client.login.submit') }}">
            @csrf

            <div class="uk-margin">
                <label class="uk-form-label">Login / Domain</label>
                <input class="uk-input" type="text" name="login" value="{{ old('login') }}" required autofocus>
                @error('login')
                    <span class="uk-text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Passwort</label>
                <input class="uk-input" type="password" name="password" required>
                @error('password')
                    <span class="uk-text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="uk-margin">
                <button type="submit" class="uk-button uk-button-primary">Anmelden</button>
            </div>
        </form>
    </div>
</div>
@endsection
