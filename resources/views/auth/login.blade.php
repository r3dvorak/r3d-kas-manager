@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-grid-large uk-child-width-1-2@m" uk-grid>

        {{-- Admin Login --}}
        <div>
            <div class="uk-card uk-card-default uk-card-body">
                <h3 class="uk-card-title uk-text-center">Admin Login</h3>
                <form method="POST" action="{{ route('login.admin.submit') }}">
                    @csrf
                    <div class="uk-margin">
                        <label class="uk-form-label">Login / Email</label>
                        <input class="uk-input" type="text" name="login" value="{{ old('login') }}" required autofocus>
                        @error('login')
                            <span class="uk-text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uk-margin">
                        <label class="uk-form-label">Passwort</label>
                        <input class="uk-input" type="password" name="password" required>
                    </div>
                    <div class="uk-margin">
                        <label><input class="uk-checkbox" type="checkbox" name="remember"> Angemeldet bleiben</label>
                    </div>
                    <div class="uk-margin">
                        <button class="uk-button uk-button-primary uk-width-1-1" type="submit">Login</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Client Login --}}
        <div>
            <div class="uk-card uk-card-default uk-card-body">
                <h3 class="uk-card-title uk-text-center">Client Login</h3>
                <form method="POST" action="{{ route('login.client.submit') }}">
                    @csrf
                    <div class="uk-margin">
                        <label class="uk-form-label">Login / Domain</label>
                        <input class="uk-input" type="text" name="login" value="{{ old('login') }}" required>
                        @error('login')
                            <span class="uk-text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uk-margin">
                        <label class="uk-form-label">Passwort</label>
                        <input class="uk-input" type="password" name="password" required>
                    </div>
                    <div class="uk-margin">
                        <label><input class="uk-checkbox" type="checkbox" name="remember"> Angemeldet bleiben</label>
                    </div>
                    <div class="uk-margin">
                        <button class="uk-button uk-button-secondary uk-width-1-1" type="submit">Login</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
