{{-- 
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.7.2-alpha
 * @date      2025-09-28
 * 
 * Login View (Unified Login: Admin & Clients mit Login/Email/Domain + Passwort)
--}}

@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-2@m">
        <h3 class="uk-card-title uk-text-center">R3D KAS Manager Login</h3>

        {{-- Fehlermeldung allgemein --}}
        @if ($errors->any())
            <div class="uk-alert-danger" uk-alert>
                <p>{{ $errors->first('login') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="uk-margin">
                <label class="uk-form-label" for="login">Login / E-Mail / Domain</label>
                <input id="login" class="uk-input" type="text" name="login" value="{{ old('login') }}" required autofocus>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label" for="password">Passwort</label>
                <input id="password" class="uk-input" type="password" name="password" required>
            </div>

            <div class="uk-margin">
                <label>
                    <input class="uk-checkbox" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    Angemeldet bleiben
                </label>
            </div>

            <div class="uk-margin">
                <button class="uk-button uk-button-primary uk-width-1-1" type="submit">
                    Anmelden
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
