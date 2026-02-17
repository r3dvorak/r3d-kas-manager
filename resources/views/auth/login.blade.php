{{-- 
 * R3D KAS Manager – Unified Login View
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.30.0-alpha
 * @date      2025-10-05
 * 
 * @license   MIT License
 * @copyright (C) 2025
--}}

@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1@m uk-width-1-2@l" style="max-width:450px; min-width:350px; margin:auto;">
        <h3 class="uk-card-title uk-text-center">{{ __('ui.login.title') }}</h3>

        <form method="POST" action="{{ route_w('login.submit') }}">
            @csrf

            {{-- Login name or domain --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="login">{{ __('ui.login.login_or_domain') }}</label>
                <div class="uk-form-controls">
                    <input id="login"
                           class="uk-input"
                           type="text"
                           name="login"
                           value="{{ old('login') }}"
                           required
                           autofocus
                           placeholder="{{ __('ui.login.login_or_domain_placeholder') }}">
                </div>
                @error('login')
                    <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                @enderror
            </div>

            {{-- Password --}}
            <div class="uk-margin">
                <label class="uk-form-label" for="password">{{ __('ui.login.password') }}</label>
                <div class="uk-form-controls">
                    <input id="password"
                           class="uk-input"
                           type="password"
                           name="password"
                           required
                           placeholder="••••••••">
                </div>
                @error('password')
                    <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                @enderror
            </div>

            {{-- Remember me --}}
            <div class="uk-margin-small">
                <label><input class="uk-checkbox" type="checkbox" name="remember"> {{ __('ui.login.remember') }}</label>
            </div>

            {{-- Submit --}}
            <div class="uk-margin">
                <button class="uk-button uk-button-primary uk-width-1-1" type="submit">
                    {{ __('ui.login.submit') }}
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
