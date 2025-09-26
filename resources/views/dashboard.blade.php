@extends('layouts.app')

@section('content')
    <h1>Welcome, {{ auth()->user()->name }}!</h1>
    <p>This is your dashboard. From here you can manage KAS Clients and Recipes.</p>
@endsection

