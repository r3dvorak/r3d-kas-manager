@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-auto" style="max-width:420px; min-width:320px;">
        
        <h3 class="uk-text-center uk-margin-small-bottom">User-Details</h3>

        <div class="uk-text-small uk-margin-small">
            <strong>Name:</strong> {{ $user->name }}
        </div>
        <div class="uk-text-small uk-margin-small">
            <strong>Login:</strong> {{ $user->login }}
        </div>
        <div class="uk-text-small uk-margin-small">
            <strong>Email:</strong> {{ $user->email ?? '—' }}
        </div>
        <div class="uk-text-small uk-margin-small">
            <strong>Rolle:</strong> {{ ucfirst($user->role) }}
        </div>
        <div class="uk-text-small uk-margin-small">
            <strong>Admin:</strong> {{ $user->is_admin ? 'Ja' : 'Nein' }}
        </div>
        <div class="uk-text-small uk-margin-small">
            <strong>Erstellt am:</strong> {{ $user->created_at->format('d.m.Y H:i') }}
        </div>

        <div class="uk-margin-top uk-flex uk-flex-between">
            <a href="{{ route('users.index') }}" class="uk-button uk-button-default">
                ← Zurück
            </a>

            @if(Auth::user()->isAdmin())
                <a href="{{ route('users.edit', $user) }}" class="uk-button uk-button-primary">
                    Bearbeiten
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
