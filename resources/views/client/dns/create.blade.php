@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>DNS-Eintrag anlegen</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route_w('client.dns.store') }}" method="POST" class="uk-form-stacked uk-card uk-card-default uk-card-body">
    @csrf

    <div class="uk-margin">
        <label class="uk-form-label">Domain</label>
        <div class="uk-form-controls">
            <select name="domain_id" class="uk-select" required>
                <option value="">Bitte waehlen</option>
                @foreach($domains as $domain)
                    <option value="{{ $domain->id }}" @selected(old('domain_id') == $domain->id)>{{ $domain->domain_full }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="uk-grid-small" uk-grid>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Typ</label>
            <select name="record_type" class="uk-select" required>
                @foreach(['A','AAAA','CNAME','MX','TXT','SRV','NS','CAA'] as $type)
                    <option value="{{ $type }}" @selected(old('record_type', 'A') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Name</label>
            <input class="uk-input" type="text" name="record_name" value="{{ old('record_name', '') }}" placeholder="@ / www / mail">
        </div>
        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Data / Value</label>
            <input class="uk-input" type="text" name="record_data" value="{{ old('record_data') }}" required>
        </div>
    </div>

    <div class="uk-margin uk-width-1-4@m">
        <label class="uk-form-label">Aux (z. B. MX-Prioritaet)</label>
        <input class="uk-input" type="number" min="0" max="65535" name="record_aux" value="{{ old('record_aux', 0) }}">
    </div>

    <div class="uk-margin-top">
        <button type="submit" class="uk-button uk-button-primary">Speichern</button>
        <a href="{{ route_w('client.dns.index') }}" class="uk-button uk-button-default">Abbrechen</a>
    </div>
</form>
@endsection
