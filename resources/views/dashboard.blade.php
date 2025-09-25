@extends('layouts.app')

@section('content')
<div class="uk-container uk-margin-large-top">
    <h2>Welcome, {{ Auth::user()->name }}!</h2>
    <p>This is your dashboard. From here you can manage KAS Clients and Recipes.</p>
</div>
@endsection
