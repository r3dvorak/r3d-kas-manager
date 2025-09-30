{{-- 
 * R3D KAS Manager – Login Auswahlseite
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.9.0-alpha
 * @date      2025-09-29
 * 
 * @license   MIT License
 * @copyright (C) 2025
--}}

@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-3@m uk-text-center">
        <h3 class="uk-card-title">Bitte wählen Sie den Login-Typ</h3>

        <div class="uk-margin">
            <a href="{{ route('login.admin') }}" class="uk-button uk-button-primary uk-width-1-1" target="_blank">Admin Login</a>
        </div>
        <div class="uk-margin">
            <a href="{{ route('login.client') }}" class="uk-button uk-button-secondary uk-width-1-1" target="_blank">KAS Client Login</a>
        </div>
    </div>
</div>
@endsection
