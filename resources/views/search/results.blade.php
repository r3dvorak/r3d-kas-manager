@extends('layouts.app')

@section('content')
    <h1>Search results for "{{ $q }}"</h1>

    <h3>Users</h3>
    <ul>
        @foreach($users as $user)
            <li>{{ $user->name }} ({{ $user->login }})</li>
        @endforeach
    </ul>

    <h3>KAS Clients</h3>
    <ul>
        @foreach($clients as $client)
            <li>{{ $client->name }} – {{ $client->login }} – {{ $client->domain }}</li>
        @endforeach
    </ul>
@endsection
