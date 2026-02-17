@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Recipe bearbeiten</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route_w('admin.recipes.update', $recipe) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.recipes._form', ['recipe' => $recipe])
    <div class="uk-margin-top">
        <button class="uk-button uk-button-primary" type="submit">Speichern</button>
        <a href="{{ route_w('admin.recipes.show', $recipe) }}" class="uk-button uk-button-default">Zurück</a>
    </div>
</form>
@endsection

