@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>Dashboard</span></h1>

    <div class="uk-grid-small uk-child-width-1-3@s" uk-grid>
        <div>
            <div class="uk-card uk-card-default uk-card-body uk-text-center">
                <span uk-icon="server" class="uk-icon-large"></span>
                <h3 class="uk-card-title">Accounts</h3>
                <p>{{ \App\Models\KasClient::count() }} registriert</p>
            </div>
        </div>
        <div>
            <div class="uk-card uk-card-default uk-card-body uk-text-center">
                <span uk-icon="world" class="uk-icon-large"></span>
                <h3 class="uk-card-title">Domains</h3>
                <p>42 verwaltet</p>
            </div>
        </div>
        <div>
            <div class="uk-card uk-card-default uk-card-body uk-text-center">
                <span uk-icon="mail" class="uk-icon-large"></span>
                <h3 class="uk-card-title">Mailboxen</h3>
                <p>123 eingerichtet</p>
            </div>
        </div>
    </div>
@endsection
