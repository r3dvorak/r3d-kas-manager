{{-- 
    R3D KAS Manager
    @package   r3d-kas-manager
    @autor     Richard Dvořák
    @version   0.28.12-alpha
    @date      2025-10-07
    @license   MIT License
--}}

@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>KAS Clients</span></h1>

<div class="uk-margin">
    <form action="{{ route('kas-clients.batch') }}" method="POST">
        @csrf

        <div class="uk-grid-small" uk-grid>
            <div class="uk-width-auto@m">
                <select class="uk-select" name="action">
                    <option value="">Batch-Aktion auswählen</option>
                    <option value="delete">Löschen</option>
                </select>
            </div>
            <div class="uk-width-auto@m">
                <button class="uk-button uk-button-primary" type="submit">ANWENDEN</button>
            </div>
            <div class="uk-width-auto@m">
                <a href="{{ route('kas-clients.create') }}" class="uk-button uk-button-secondary">NEU</a>
            </div>
        </div>

        <table class="uk-table uk-table-divider uk-table-small uk-margin-top uk-table-striped uk-table-hover uk-table-condensed">
            <thead>
                <tr>
                    <th style="width:30px;">
                        <input type="checkbox" class="uk-checkbox" id="select-all">
                    </th>
                    <th class="uk-text-uppercase">Name / Login / Domains</th>
                    <th class="uk-text-uppercase uk-text-nowrap uk-text-center">Aktionen</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($kasClients as $client)
                <tr>
                    <td>
                        <input type="checkbox" class="uk-checkbox" name="ids[]" value="{{ $client->id }}">
                    </td>

                    <td>
                        {{-- ✅ FIXED NAME / LOGIN DISPLAY --}}
                        <div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small-bottom">
                            <div>
                                <strong>{{ $client->account_comment }}</strong>
                                @if($client->account_login)
                                    <span class="uk-text-muted uk-margin-small-left">{{ $client->account_login }}</span>
                                @elseif($client->account_comment)
                                    <span class="uk-text-muted uk-margin-small-left">{{ $client->account_comment }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Server --}}
                        @if($client->server_hostname || $client->server_ip || $client->server_internal_domain)
                            <div class="uk-text-small uk-text-muted">
                                <strong>Server:</strong>
                                {{ $client->server_hostname ?: '—' }}
                                @if($client->server_ip)
                                    · {{ $client->server_ip }}
                                @endif
                                @if($client->server_internal_domain)
                                    · {{ $client->server_internal_domain }}
                                @endif
                            </div>
                        @endif

                        {{-- Domains --}}
                        @if($client->domains->count())
                            <div class="uk-text-small uk-margin-small-top">
                                <strong>Domains:</strong>
                                {{ $client->domains->pluck('domain_full')->implode(', ') }}
                            </div>
                        @endif

                        {{-- Subdomains --}}
                        @php
                            $subdomains = $client->domains->flatMap->subdomains;
                        @endphp
                        @if($subdomains->count())
                            <div class="uk-text-small uk-text-muted">
                                <strong>Subdomains:</strong>
                                {{ $subdomains->pluck('subdomain_full')->implode(', ') }}
                            </div>
                        @endif
                    </td>

                    {{-- Aktionen --}}
                    <td class="uk-text-center uk-text-nowrap uk-flex uk-flex-center uk-flex-middle uk-height-1-1 kas-client-actions">
                        <a href="{{ route('kas-clients.show', $client->id) }}" class="uk-icon-button action-icon-btn" uk-icon="icon: eye" title="Anzeigen"></a>
                        <a href="{{ route('kas-clients.edit', $client->id) }}" class="uk-icon-button action-icon-btn" uk-icon="icon: pencil" title="Bearbeiten"></a>
                        <a href="{{ route_w('kas-clients.impersonate.generate', [$client->id]) }}" target="_blank" rel="noopener noreferrer" class="uk-icon-button action-icon-btn" uk-icon="icon: sign-in" title="Impersonate"></a>
                        <form action="{{ route('kas-clients.destroy', $client->id) }}" method="POST" class="kas-client-delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="uk-icon-button action-icon-btn action-icon-btn-danger" uk-icon="icon: trash" title="Löschen" onclick="return confirmDeleteTwice('Wirklich löschen?', 'LOESCHEN')"></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </form>
</div>

<script>
    // Checkbox Select-All Funktion
    document.getElementById('select-all').addEventListener('change', function() {
        document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
    });
</script>
@endsection
