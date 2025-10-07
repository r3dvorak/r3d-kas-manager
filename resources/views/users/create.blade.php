@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body" style="max-width:650px; min-width:350px;">

        <h2 class="uk-heading-line uk-text-center"><span>User anlegen</span></h2>
        @if ($errors->any())
            <div class="uk-alert-danger" uk-alert>
                <ul class="uk-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        

        <form class="uk-form-stacked" action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="uk-margin">
                <input class="uk-input" type="text" name="name" placeholder="Name" required>
            </div>

            <div class="uk-margin">
                <input class="uk-input" type="text" name="login" placeholder="Login" required>
            </div>

            <div class="uk-margin">
                <input class="uk-input" type="email" name="email" placeholder="E-Mail">
            </div>

            <div class="uk-margin">
                <input class="uk-input" type="password" name="password" placeholder="Passwort" required>
            </div>

            <div class="uk-margin">
                <input class="uk-input" type="password" name="password_confirmation" placeholder="Passwort bestätigen" required>
            </div>

            <div class="uk-margin">
                <select class="uk-select" name="role" required>
                    <option value="" disabled selected>Rolle wählen</option>
                    <option value="user">User (Read/Write)</option>
                    <option value="admin">Admin (Vollzugriff)</option>
                </select>
            </div>

            <div class="uk-margin">
                <button type="submit" class="uk-button uk-button-primary uk-width-1-1">Speichern</button>
                <a href="{{ route('users.index') }}" class="uk-button uk-button-default uk-width-1-1 uk-margin-small-top">Abbrechen</a>
            </div>
        </form>

    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    const passwordInput = form.querySelector("input[name='password']");
    const loginInput = form.querySelector("input[name='login']");
    const nameInput = form.querySelector("input[name='name']");

    form.addEventListener("submit", (e) => {
        let messages = [];

        if (!nameInput.value.trim()) messages.push("Bitte einen Namen eingeben.");
        if (!loginInput.value.trim()) messages.push("Bitte einen Login eingeben.");
        if (passwordInput.value.length < 4) messages.push("Das Passwort muss mindestens 4 Zeichen lang sein.");

        if (messages.length > 0) {
            e.preventDefault();
            UIkit.notification({
                message: messages.join("<br>"),
                status: 'warning',
                pos: 'top-center',
                timeout: 4000
            });
        }
    });
});
</script>
@endsection


