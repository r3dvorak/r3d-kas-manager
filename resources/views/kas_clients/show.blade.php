@extends('layouts.app')

@section('content')
<div class="uk-flex uk-flex-center">
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1@m" style="max-width:800px;">

        <h2 class="uk-heading-line"><span>{{ $kasClient->account_comment ?: 'KAS Client' }}</span></h2>

        <div class="uk-margin-small-bottom uk-text-small uk-text-muted">
            <strong>Login:</strong> {{ $kasClient->account_login ?? '—' }} &nbsp;|&nbsp;
            <strong>Kontakt:</strong> {{ $kasClient->account_contact_mail ?: '—' }}
        </div>

        <hr class="uk-margin-small">

        {{-- Server --}}
        <h4 class="uk-margin-remove-top">Server</h4>
        <div class="uk-text-small">
            <div><strong>all-inkl Kundennummer:</strong> {{ $kasClient->all_inkl_customer_number ?: '—' }}</div>
            <div><strong>all-inkl Vertragsnummer:</strong> {{ $kasClient->all_inkl_contract_number ?: '—' }}</div>
            <div><strong>Interne Account-Domain:</strong> {{ $kasClient->server_internal_domain ?: '—' }}</div>
            <div><strong>Server-Hostname:</strong> {{ $kasClient->server_hostname ?: '—' }}</div>
            <div><strong>Server-IP:</strong> {{ $kasClient->server_ip ?: '—' }}</div>
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
