@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>DNS-Eintrag bearbeiten</span></h1>

@if($errors->any())
    <div class="uk-alert-danger" uk-alert>
        <ul class="uk-margin-remove">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route_w('client.dns.update', $dnsRecord) }}" method="POST" class="uk-form-stacked uk-card uk-card-default uk-card-body">
    @csrf
    @method('PUT')

    <div class="uk-margin">
        <label class="uk-form-label">Domain</label>
        <div class="uk-form-controls">
            <select name="domain_id" class="uk-select" required>
                @foreach($domains as $domain)
                    <option value="{{ $domain->id }}" @selected((int) old('domain_id', $dnsRecord->domain_id) === (int) $domain->id)>{{ $domain->domain_full }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="uk-grid-small" uk-grid>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Typ</label>
            <select name="record_type" class="uk-select" required>
                @foreach(['A','AAAA','CNAME','MX','TXT','SRV','NS','CAA'] as $type)
                    <option value="{{ $type }}" @selected(old('record_type', strtoupper((string) $dnsRecord->record_type)) === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="uk-width-1-4@m">
            <label class="uk-form-label">Name</label>
            <input class="uk-input" type="text" name="record_name" value="{{ old('record_name', (string) $dnsRecord->record_name) }}">
        </div>
        <div class="uk-width-1-2@m">
            <label class="uk-form-label">Data / Value</label>
            <input class="uk-input" type="text" name="record_data" value="{{ old('record_data', (string) $dnsRecord->record_data) }}" required>
        </div>
    </div>

    <div class="uk-margin uk-width-1-4@m">
        <label class="uk-form-label">Aux</label>
        <input class="uk-input" type="number" min="0" max="65535" name="record_aux" value="{{ old('record_aux', (int) ($dnsRecord->record_aux ?? 0)) }}">
    </div>

    <div class="uk-margin-top">
        <button type="submit" class="uk-button uk-button-primary">Aktualisieren</button>
        <a href="{{ route_w('client.dns.index') }}" class="uk-button uk-button-default">Zurueck</a>
    </div>
</form>
@endsection
