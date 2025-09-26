@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>KAS Client Details</span></h1>

    <table class="uk-table uk-table-divider uk-table-small">
        <tr>
            <th>Name</th>
            <td>{{ $kasClient->name }}</td>
        </tr>
        <tr>
            <th>Login</th>
            <td>{{ $kasClient->login }}</td>
        </tr>
        <tr>
            <th>Domain</th>
            <td>{{ $kasClient->domain }}</td>
        </tr>
        <tr>
            <th>API Passwort</th>
            <td>••••••••</td>
        </tr>
    </table>

    <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">Zurück</a>
    <a href="{{ route('kas-clients.edit', $kasClient) }}" class="uk-button uk-button-primary">Bearbeiten</a>
@endsection
