{{-- resources/views/client/dashboard.blade.php --}}
{{-- 
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.6-alpha
 * @date      2025-09-26
 *
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 *
 * Dashboard for KAS Client login session
--}}

@extends('layouts.app')

@section('content')
<div class="uk-container">
    <h1 class="uk-heading-line"><span>Client Dashboard</span></h1>

    <div class="uk-card uk-card-default uk-card-body uk-margin">
        <h3 class="uk-card-title">Willkommen, {{ Auth::guard('kas_client')->user()->name }}</h3>
        <p>Sie sind als KAS-Client eingeloggt.</p>

        <ul class="uk-list uk-list-divider">
            <li>
                <strong>Login:</strong> {{ Auth::guard('kas_client')->user()->login }}
            </li>
            <li>
                <strong>Domain:</strong> {{ Auth::guard('kas_client')->user()->domain }}
            </li>
            <li>
                <strong>API-User:</strong> {{ Auth::guard('kas_client')->user()->api_user }}
            </li>
        </ul>
    </div>

    <div class="uk-grid-small uk-child-width-1-2@s" uk-grid>
        <div>
            <div class="uk-card uk-card-hover uk-card-default uk-card-body">
                <h3 class="uk-card-title">Meine Domains</h3>
                <p>Liste und Verwaltung der Domains dieses Clients.</p>
                <a href="{{ route('client.domains.index') }}" class="uk-button uk-button-primary">Domains ansehen</a>
            </div>
        </div>
        <div>
            <div class="uk-card uk-card-hover uk-card-default uk-card-body">
                <h3 class="uk-card-title">Mailkonten</h3>
                <p>Mailboxen und Weiterleitungen verwalten.</p>
                <a href="{{ route('client.mailboxes.index') }}" class="uk-button uk-button-primary">Mailkonten ansehen</a>
            </div>
        </div>
        <div>
            <div class="uk-card uk-card-hover uk-card-default uk-card-body">
                <h3 class="uk-card-title">DNS Einstellungen</h3>
                <p>DNS Records dieses Clients verwalten.</p>
                <a href="{{ route('client.dns.index') }}" class="uk-button uk-button-primary">DNS Einstellungen</a>
            </div>
        </div>
        <div>
            <div class="uk-card uk-card-hover uk-card-default uk-card-body">
                <h3 class="uk-card-title">Rezepte</h3>
                <p>Automatisierungen für Domains, Mailboxen und DNS.</p>
                <a href="{{ route('client.recipes.index') }}" class="uk-button uk-button-primary">Rezepte öffnen</a>
            </div>
        </div>
    </div>

    <div class="uk-margin-top">
        <a href="{{ route('kas-client.logout') }}" class="uk-button uk-button-danger">Abmelden</a>
    </div>
</div>
@endsection
