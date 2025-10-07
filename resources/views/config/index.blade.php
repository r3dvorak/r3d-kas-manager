{{-- 
    R3D KAS Manager
    @package   r3d-kas-manager
    @author    Richard Dvořák
    @version   0.13.0-alpha
    @date      2025-09-26
    @license   MIT License
--}}
@extends('layouts.app')

@section('content')
<div class="uk-width-2-3@m">
    <h2 class="uk-heading-line"><span>Systemeinstellungen</span></h2>

    @if(session('success'))
        <div class="uk-alert-success" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <form action="{{ route('config.update') }}" method="POST" enctype="multipart/form-data" class="uk-form-stacked uk-margin-large-top">
        @csrf

        {{-- Sicherheit --}}
        <fieldset class="uk-fieldset">
            <legend class="uk-legend">Sicherheit</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Session-Timeout (Minuten)</label>
                <input class="uk-input" type="number" name="session_timeout"
                       value="{{ $settings['session_timeout'] ?? 30 }}" min="5" max="240">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Absolute Sitzungslänge (Minuten)</label>
                <input class="uk-input" type="number" name="absolute_session_max"
                       value="{{ $settings['absolute_session_max'] ?? 180 }}" min="30" max="1440">
            </div>
        </fieldset>

        {{-- System --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">System</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Support-E-Mail</label>
                <input class="uk-input" type="email" name="support_email"
                       value="{{ $settings['support_email'] ?? 'info@r3d.de' }}">
            </div>

            <div class="uk-margin">
                <label class="uk-form-label">Seitentitel / Name</label>
                <input class="uk-input" type="text" name="site_name"
                       value="{{ $settings['site_name'] ?? 'R3D KAS Manager' }}">
            </div>
        </fieldset>

        {{-- Branding / Logo --}}
        <fieldset class="uk-fieldset uk-margin-large-top">
            <legend class="uk-legend">Branding</legend>

            <div class="uk-margin">
                <label class="uk-form-label">Logo-Upload (SVG, PNG, JPG, GIF max 1 MB)</label>
                <input class="uk-input" type="file" name="logo_file" accept=".svg,.png,.jpg,.jpeg,.gif">
            </div>

            @if(!empty($settings['logo_url']))
                <div class="uk-margin-small-top uk-text-center">
                    <img src="{{ $settings['logo_url'] }}" alt="Current Logo" style="max-height:80px;">
                    <div class="uk-text-meta">{{ $settings['logo_url'] }}</div>
                </div>
            @endif
        </fieldset>

        <button type="submit" class="uk-button uk-button-primary uk-margin-top">Speichern</button>
    </form>
</div>
@endsection
