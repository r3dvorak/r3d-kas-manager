@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>KAS Clients</span></h1>

    <a href="{{ route('kas-clients.create') }}" class="uk-button uk-button-primary uk-margin-small-bottom">Add Client</a>

    @if(session('success'))
        <div class="uk-alert-success" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <table class="uk-table uk-table-divider uk-table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>API User</th>
                <th>API URL</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->api_user }}</td>
                    <td>{{ $client->api_url }}</td>
                    <td>
                        <a href="{{ route('kas-clients.show', $client) }}" class="uk-button uk-button-small uk-button-default">View</a>
                        <a href="{{ route('kas-clients.edit', $client) }}" class="uk-button uk-button-small uk-button-secondary">Edit</a>
                        <form action="{{ route('kas-clients.destroy', $client) }}" method="POST" class="uk-display-inline">
                            @csrf
                            @method('DELETE')
                            <button class="uk-button uk-button-small uk-button-danger" onclick="return confirm('Delete this client?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
