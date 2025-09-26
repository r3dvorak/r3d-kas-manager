@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>User anlegen</span></h1>

    <form class="uk-form-stacked" action="{{ route('users.store') }}" method="POST">
        @csrf

        <div class="uk-margin">
            <label class="uk-form-label">Name</label>
            <input class="uk-input" type="text" name="name" required>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Login</label>
            <input class="uk-input" type="text" name="login" required>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Email</label>
            <input class="uk-input" type="email" name="email">
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Passwort</label>
            <input class="uk-input" type="password" name="password" required>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Rolle</label>
            <select class="uk-select" name="role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">KAS Client</label>
            <select class="uk-select" name="kas_client_id">
                <option value="">-- keiner --</option>
                @foreach($kasClients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="uk-button uk-button-primary">Speichern</button>
        <a href="{{ route('users.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </form>
@endsection
