{{-- 
 * R3D KAS Manager – Login Auswahlseite
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.8.0-alpha
 * @date      2025-09-29
 * @license   MIT License
 * 
 * resources\views\auth\login_select.blade.php
 * 
 * Login Auswahlseite (Buttons für Admin / Client)
--}}

@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center uk-margin-large-top">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-3@m uk-text-center">
        <h3 class="uk-card-title">R3D KAS Manager</h3>
        <p>Bitte wählen Sie den Login-Bereich:</p>
        <a href="{{ route('login.admin') }}" class="uk-button uk-button-primary uk-margin-small">Admin Login</a>
        <a href="{{ route('login.client') }}" class="uk-button uk-button-secondary uk-margin-small">Client Login</a>
    </div>
</div>
@endsection
