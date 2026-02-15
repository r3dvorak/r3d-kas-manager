@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Subdomains: Pruefung (KAS vs DB)</span></h1>

<div class="uk-grid-small uk-child-width-1-2@m" uk-grid>
    <div>
        <div class="uk-card uk-card-default uk-card-body">
            <h3 class="uk-card-title">Remote (KAS): fehlt lokal</h3>
            @if(empty($diff['to_add']))
                <p class="uk-text-muted">Keine.</p>
            @else
                <ul class="uk-list uk-list-divider">
                    @foreach($diff['to_add'] as $d)
                        <li class="uk-text-break">{{ $d }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    <div>
        <div class="uk-card uk-card-default uk-card-body">
            <h3 class="uk-card-title">Lokal (DB): fehlt remote</h3>
            @if(empty($diff['to_remove']))
                <p class="uk-text-muted">Keine.</p>
            @else
                <ul class="uk-list uk-list-divider">
                    @foreach($diff['to_remove'] as $d)
                        <li class="uk-text-break">{{ $d }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<div class="uk-margin-top">
    <a class="uk-button uk-button-default" href="{{ route('client.subdomains.index') }}">Zurueck</a>
</div>
@endsection

