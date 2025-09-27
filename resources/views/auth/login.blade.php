{{-- 
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.7.1-alpha
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
    <div class="uk-card uk-card-default uk-card-body uk-width-1-2@m">
        <h3 class="uk-card-title uk-text-center">R3D KAS Manager Login</h3>

        <form method="POST" action="{{ route('login.submit') }}">
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
                <label><input class="uk-checkbox" type="checkbox" name="remember"> Angemeldet bleiben</label>
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
