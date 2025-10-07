@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1@m" style="max-width:800px;">

        <h2 class="uk-heading-line"><span>{{ $kasClient->name }}</span></h2>

        <div class="uk-margin-small-bottom uk-text-small uk-text-muted">
            <strong>Login:</strong> {{ $kasClient->login }} &nbsp;|&nbsp;
            <strong>Email:</strong> {{ $kasClient->email ?? '—' }} &nbsp;|&nbsp;
            <strong>API User:</strong> {{ $kasClient->api_user }}
        </div>

        <hr class="uk-margin-small">

        {{-- Domains --}}
        <h4 class="uk-margin-remove-top">Domains</h4>

        @if($kasClient->domains->isEmpty())
            <p class="uk-text-muted">Keine Domains zugeordnet.</p>
        @else
            <ul class="uk-list uk-list-divider">
                @foreach($kasClient->domains as $domain)
                    <li>
                        <div class="uk-flex uk-flex-between uk-flex-middle">
                            <div>
                                <strong>{{ $domain->domain_full }}</strong>
                                @if($domain->active)
                                    <span class="uk-label uk-label-success">aktiv</span>
                                @else
                                    <span class="uk-label uk-label-warning">inaktiv</span>
                                @endif
                                <div class="uk-text-muted uk-text-small">
                                    {{ $domain->domain_path }} · PHP {{ $domain->php_version }} 
                                    @if($domain->ssl_status)
                                        · SSL aktiv
                                    @endif
                                </div>
                            </div>
                            <div class="uk-text-muted uk-text-small">
                                @if($domain->subdomains->count() > 0)
                                    {{ $domain->subdomains->count() }} Subdomains
                                @endif
                            </div>
                        </div>

                        {{-- Subdomains as inline list --}}
                        @if($domain->subdomains->count() > 0)
                            <div class="uk-margin-small-top uk-text-small">
                                @foreach($domain->subdomains as $sub)
                                    <span class="uk-badge uk-margin-small-right">{{ $sub->subdomain_full }}</span>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <hr>

        {{-- Footer Buttons --}}
        <div class="uk-margin-top">
            <a href="{{ route('kas-clients.index') }}" class="uk-button uk-button-default">Zurück</a>
            <a href="{{ route('kas-clients.edit', $kasClient) }}" class="uk-button uk-button-primary">Bearbeiten</a>
        </div>

    </div>
</div>
@endsection
