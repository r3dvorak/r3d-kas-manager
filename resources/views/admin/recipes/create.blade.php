@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Recipe erstellen</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route_w('admin.recipes.store') }}" method="POST">
    @csrf
    @include('admin.recipes._form')
    <div class="uk-margin-top">
        <button class="uk-button uk-button-primary" type="submit">Speichern</button>
        <a href="{{ route_w('admin.recipes.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </div>
</form>
@endsection

