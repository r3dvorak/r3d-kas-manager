{{-- 
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.0-alpha
 * @date      2025-09-24
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * Login View (KAS-Style: Login/Domain + Passwort)
--}}

@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-large">
        <h3 class="uk-card-title">Login</h3>

        @if ($errors->any())
            <div class="uk-alert-danger" uk-alert>
                <p><strong>Fehler:</strong> {{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="uk-margin">
                <label class="uk-form-label">Login oder Domain</label>
                <div class="uk-form-controls">
                    <input class="uk-input" type="text" name="login" value="{{ old('login') }}" required autofocus>
                </div>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Passwort</label>
                <div class="uk-form-controls">
                    <input class="uk-input" type="password" name="password" required>
                </div>
            </div>

            <div class="uk-margin">
                <label><input class="uk-checkbox" type="checkbox" name="remember"> Eingeloggt bleiben</label>
            </div>

            <div class="uk-margin">
                <button type="submit" class="uk-button uk-button-primary uk-width-1-1">Login</button>
            </div>
        </form>

    </div>
</div>
@endsection
