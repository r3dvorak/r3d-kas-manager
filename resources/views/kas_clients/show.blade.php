@extends('layouts.app')

@section('content')
<div class="uk-container">
    <h2 class="uk-heading-line"><span>Client Details</span></h2>

    <ul class="uk-list uk-list-striped">
        <li><strong>ID:</strong> {{ $kasClient->id }}</li>
        <li><strong>Name:</strong> {{ $kasClient->name }}</li>
        <li><strong>API User:</strong> {{ $kasClient->api_user }}</li>
        <li><strong>API Password:</strong> {{ $kasClient->api_password }}</li>
        <li><strong>Created:</strong> {{ $kasClient->created_at }}</li>
        <li><strong>Updated:</strong> {{ $kasClient->updated_at }}</li>
    </ul>

    <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">Back</a>
    <a href="{{ route('kas-clients.edit', $kasClient->id) }}" class="uk-button uk-button-primary">Edit</a>
</div>
@endsection
