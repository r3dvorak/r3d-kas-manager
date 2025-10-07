{{-- 
    R3D KAS Manager – Create KAS Client
    @package   r3d-kas-manager
    @author    Richard Dvořák
    @version   0.14.8-alpha
    @date      2025-10-06
    @license   MIT License
--}}

@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1@m" style="max-width:600px;">

        <h2 class="uk-heading-line"><span>Neuen KAS Client anlegen</span></h2>

        @if ($errors->any())
            <div class="uk-alert-danger" uk-alert>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="kasClientForm" class="uk-form-stacked" method="POST" action="{{ route('kas-clients.store') }}">
            @csrf

            {{-- Client Name --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="name">Name</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="name" name="name" type="text"
                        placeholder="z. B. 000 R3D & Trimains" required>
                    <small class="uk-text-danger uk-hidden" id="error-name">Bitte einen Namen eingeben.</small>
                </div>
            </div>

            {{-- Login --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="login">Login</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="login" name="login" type="text"
                        placeholder="z. B. w01e77bc" required>
                    <small class="uk-text-danger uk-hidden" id="error-login">Bitte einen Login angeben.</small>
                </div>
            </div>

            {{-- Email --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="email">E-Mail</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="email" name="email" type="email"
                        placeholder="z. B. faktura@domain.de">
                    <small class="uk-text-danger uk-hidden" id="error-email">Bitte eine gültige E-Mail eingeben.</small>
                </div>
            </div>

            {{-- Password (used for both API + Login) --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="password">Passwort</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="password" name="password" type="password"
                        placeholder="Mindestens 8 Zeichen" required minlength="8">
                    <small class="uk-text-danger uk-hidden" id="error-password">Passwort muss mindestens 8 Zeichen haben.</small>
                </div>
            </div>

            <hr>

            <div class="uk-flex uk-flex-between">
                <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">← Abbrechen</a>
                <button type="submit" class="uk-button uk-button-primary">Speichern</button>
            </div>
        </form>

    </div>
</div>

{{-- Inline validation script --}}
<script>
document.getElementById('kasClientForm').addEventListener('submit', function (e) {

    console.log('Submitting form:', Object.fromEntries(new FormData(this)));
    
    let valid = true;

    const fields = ['name', 'login', 'email', 'password'];

    fields.forEach(id => {
        const input = document.getElementById(id);
        const error = document.getElementById('error-' + id);
        input.classList.remove('uk-form-danger');
        error.classList.add('uk-hidden');
    });

    // Validate Name
    const name = document.getElementById('name');
    if (!name.value.trim()) {
        showError('name');
        valid = false;
    }

    // Validate Login
    const login = document.getElementById('login');
    if (!login.value.trim()) {
        showError('login');
        valid = false;
    }

    // Validate Email (if filled)
    const email = document.getElementById('email');
    if (email.value && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email.value)) {
        showError('email');
        valid = false;
    }

    // Validate Password
    const password = document.getElementById('password');
    if (password.value.length < 8) {
        showError('password');
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
    }

    function showError(id) {
        const input = document.getElementById(id);
        const error = document.getElementById('error-' + id);
        input.classList.add('uk-form-danger');
        error.classList.remove('uk-hidden');
    }
});
</script>
@endsection
