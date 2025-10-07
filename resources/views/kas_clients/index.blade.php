{{-- 
    R3D KAS Manager
    @package   r3d-kas-manager
    @autor     Richard Dvořák
    @version   0.17.8-alpha
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
                    <option value="activate">Aktivieren</option>
                    <option value="deactivate">Deaktivieren</option>
                    <option value="archive">Archivieren</option>
                    <option value="delete">Löschen</option>
                    <option value="duplicate">Duplizieren</option>
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
                    <td class="uk-text-center uk-text-nowrap uk-flex uk-flex-center uk-flex-middle uk-height-1-1">
                        <a href="{{ route('kas-clients.show', $client->id) }}" uk-icon="icon: eye" title="Anzeigen"></a>
                        <a href="{{ route('kas-clients.edit', $client->id) }}" uk-icon="icon: pencil" title="Bearbeiten" class="uk-margin-small-left"></a>
                        <a href="{{ route('kas-clients.impersonate.generate', $client->id) }}" uk-icon="icon: sign-in" title="Impersonate" class="uk-margin-small-left"></a>
                        <form action="{{ route('kas-clients.destroy', $client->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="uk-button uk-button-link uk-text-danger uk-margin-small-left" uk-icon="icon: trash" title="Löschen" onclick="return confirm('Wirklich löschen?')"></button>
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
