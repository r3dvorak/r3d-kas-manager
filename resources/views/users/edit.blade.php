@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body" style="max-width:450px; min-width:350px;">
        <h3 class="uk-card-title uk-text-center">User bearbeiten</h3>

        <form class="uk-form-stacked" action="{{ route('users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="uk-margin">
                <input class="uk-input" type="text" name="name" value="{{ old('name', $user->name) }}" placeholder="Name" required>
                @error('name')
                    <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                @enderror
            </div>

            <div class="uk-margin">
                <input class="uk-input" type="text" name="login" value="{{ old('login', $user->login) }}" placeholder="Login" required>
                @error('login')
                    <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                @enderror
            </div>

            <div class="uk-margin">
                <input class="uk-input" type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="E-Mail-Adresse">
                @error('email')
                    <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                @enderror
            </div>

            <div class="uk-margin">
                <input class="uk-input" type="password" name="password" placeholder="Neues Passwort (leer lassen, wenn unverändert)">
                @error('password')
                    <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                @enderror
            </div>

            <div class="uk-margin">
                <input class="uk-input" type="password" name="password_confirmation" placeholder="Passwort bestätigen">
                @error('password_confirmation')
                    <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                @enderror
            </div>

            <div class="uk-margin">
                <select class="uk-select" name="role" required>
                    <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
                @error('role')
                    <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                @enderror
            </div>

            <div class="uk-flex uk-flex-between uk-margin-top">
                <a href="{{ route('users.index') }}" class="uk-button uk-button-default">← Zurück</a>
                <button type="submit" class="uk-button uk-button-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>
@endsection
