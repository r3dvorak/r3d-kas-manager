@extends('layouts.app')

@section('content')
<div class="uk-container">
    <h2 class="uk-heading-line"><span>Add Client</span></h2>

    @if ($errors->any())
        <div class="uk-alert-danger" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="uk-form-stacked" action="{{ route('kas-clients.store') }}" method="POST">
        @csrf
        <div class="uk-margin">
            <label class="uk-form-label">Name</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="name" value="{{ old('name') }}" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">API User</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="text" name="api_user" value="{{ old('api_user') }}" required>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label">API Password</label>
            <div class="uk-form-controls">
                <input class="uk-input" type="password" name="api_password" required>
            </div>
        </div>

        <button class="uk-button uk-button-primary">Save</button>
        <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">Cancel</a>
    </form>
</div>
@endsection
