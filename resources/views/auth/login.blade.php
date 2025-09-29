{{-- 
 * R3D KAS Manager – Login View (Admin / KAS Client)
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.8.0-alpha
 * @date      2025-09-29
 * 
 * @license   MIT License
 * @copyright (C) 2025
--}}

@extends('layouts.app')

@section('content')
@php
    // Prüfen, ob gerade Admin- oder Client-Login aktiv ist
    $isAdmin = request()->routeIs('login.admin*');
    $title   = $isAdmin ? 'ADMIN Login' : 'KAS Client Login';
    $action  = $isAdmin ? route('login.admin.submit') : route('login.client.submit');
@endphp

<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-2@m">
        <h3 class="uk-card-title uk-text-center">{{ $title }}</h3>

        <form method="POST" action="{{ $action }}">
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

        <div class="uk-margin uk-text-center">
            <a href="{{ route('login') }}">← Zurück zur Auswahl</a>
        </div>
    </div>
</div>
@endsection
