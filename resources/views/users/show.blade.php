@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>User Details</span></h1>

    <table class="uk-table uk-table-divider uk-table-small">
        <tr>
            <th>Name</th>
            <td>{{ $user->name }}</td>
        </tr>
        <tr>
            <th>Login</th>
            <td>{{ $user->login }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <th>Rolle</th>
            <td>{{ $user->role }}</td>
        </tr>
        <tr>
            <th>KAS Client</th>
            <td>{{ optional($user->kasClient)->name ?? '–' }}</td>
        </tr>
    </table>

    <a href="{{ route('users.index') }}" class="uk-button uk-button-default">Zurück</a>
    <a href="{{ route('users.edit', $user) }}" class="uk-button uk-button-primary">Bearbeiten</a>
@endsection
