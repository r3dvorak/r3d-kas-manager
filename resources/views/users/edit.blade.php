@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>User bearbeiten</span></h1>

    <form class="uk-form-stacked" action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="uk-margin">
            <label class="uk-form-label">Name</label>
            <input class="uk-input" type="text" name="name" value="{{ $user->name }}" required>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Login</label>
            <input class="uk-input" type="text" name="login" value="{{ $user->login }}" required>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Email</label>
            <input class="uk-input" type="email" name="email" value="{{ $user->email }}">
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Passwort (leer lassen = unverändert)</label>
            <input class="uk-input" type="password" name="password">
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">Rolle</label>
            <select class="uk-select" name="role" required>
                <option value="user" @if($user->role==='user') selected @endif>User</option>
                <option value="admin" @if($user->role==='admin') selected @endif>Admin</option>
            </select>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">KAS Client</label>
            <select class="uk-select" name="kas_client_id">
                <option value="">-- keiner --</option>
                @foreach($kasClients as $client)
                    <option value="{{ $client->id }}" @if($user->kas_client_id==$client->id) selected @endif>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="uk-button uk-button-primary">Aktualisieren</button>
        <a href="{{ route('users.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </form>
@endsection
