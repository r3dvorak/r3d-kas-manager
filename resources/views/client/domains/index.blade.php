@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Domain</span></h1>
<p class="uk-text-small uk-text-muted uk-margin-remove-top">
    Eine Domain ist im Internet die Adresse, unter der eine Website erreichbar ist, sozusagen der "Name" einer Website.
</p>

@if(session('info'))
    <div class="uk-alert-primary" uk-alert><p>{{ session('info') }}</p></div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small">
    <div class="uk-text-small">
        <strong>Angelegte Domains:</strong> {{ $domains->total() }}
        @if($domains->total() > 0)
            <span class="uk-text-muted">| Anzeigen {{ $domains->firstItem() }} - {{ $domains->lastItem() }} von {{ $domains->total() }}</span>
        @endif
    </div>
    <div class="uk-flex uk-flex-middle uk-grid-small" uk-grid>
        <div>
            <a class="uk-button uk-button-primary" href="{{ route_w('client.coming-soon', ['resource' => 'domains']) }}">{{ __('ui.common.new') }}</a>
        </div>
        <div>
            <a class="uk-button uk-button-default" href="{{ route_w('client.domains.preview') }}"><span uk-icon="refresh" class="uk-margin-small-right"></span>{{ __('ui.common.check_changes') }}</a>
        </div>
        <div>
            <form action="{{ route_w('client.domains.sync') }}" method="POST" style="display:inline;">
                @csrf
                <button class="uk-button uk-button-secondary" type="submit" onclick="return confirm('{{ __('ui.client.sync_confirm_default') }}')"><span uk-icon="future" class="uk-margin-small-right"></span>{{ __('ui.common.sync_now') }}</button>
            </form>
        </div>
    </div>
</div>

<form class="uk-grid-small uk-margin" uk-grid method="GET" action="{{ route_w('client.domains.index') }}">
    <div class="uk-width-1-2@m">
        <input class="uk-input" type="text" name="q" value="{{ $q }}" placeholder="Suche (domain, pfad)...">
    </div>
    <div class="uk-width-auto@m">
        <button class="uk-button uk-button-primary" type="submit">{{ __('ui.common.search') }}</button>
    </div>
</form>

<div class="uk-overflow-auto">
    <table class="uk-table uk-table-small uk-table-divider uk-table-striped">
        <thead>
            <tr>
                <th>Domain</th>
                <th>Status</th>
                <th>Ziel</th>
                <th class="uk-text-nowrap">PHP</th>
                <th class="uk-text-nowrap">SSL</th>
                <th class="uk-text-nowrap">Subdomains</th>
            </tr>
        </thead>
        <tbody>
            @forelse($domains as $d)
                <tr>
                    <td class="uk-text-nowrap"><strong>{{ $d->label() }}</strong></td>
                    <td class="uk-text-nowrap">
                        @if(($d->is_active ?? 'Y') === 'Y')
                            <span class="uk-label uk-label-success">{{ __('ui.common.active') }}</span>
                        @else
                            <span class="uk-label uk-label-warning">{{ __('ui.common.inactive') }}</span>
                        @endif
                    </td>
                    <td style="max-width: 520px; white-space: normal;">{{ $d->domain_path ?: '—' }}</td>
                    <td class="uk-text-nowrap">{{ $d->php_version ?: '—' }}</td>
                    <td class="uk-text-nowrap">
                        @if(($d->ssl_proxy ?? 'N') === 'Y' || ($d->ssl_certificate_ip ?? 'N') === 'Y' || ($d->ssl_certificate_sni ?? 'N') === 'Y')
                            <span class="uk-label uk-label-success">aktiv</span>
                        @else
                            <span class="uk-label">—</span>
                        @endif
                    </td>
                    <td class="uk-text-nowrap">{{ $d->subdomains_count }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="uk-text-muted">Noch keine Domains in der DB.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="uk-margin-top">
    {{ $domains->links() }}
</div>
@endsection
