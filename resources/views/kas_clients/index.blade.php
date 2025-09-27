{{-- 
    R3D KAS Manager
    @package   r3d-kas-manager
    @author    Richard Dvořák
    @version   0.6.3-alpha
    @date      2025-09-26
    @license   MIT License
--}}

@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>KAS Clients</span></h1>

    @if(session('success'))
        <div class="uk-alert-success" uk-alert>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="uk-alert-danger" uk-alert>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <form action="{{ route('kas-clients.batch') }}" method="POST">
        @csrf

        <div class="uk-margin-small">
            <select name="action" class="uk-select uk-width-medium">
                <option value="">Batch-Aktion auswählen</option>
                <option value="activate">Aktivieren</option>
                <option value="deactivate">Deaktivieren</option>
                <option value="archive">Archivieren</option>
                <option value="delete">Löschen</option>
                <option value="duplicate">Duplizieren</option>
            </select>
            <button type="submit" class="uk-button uk-button-primary uk-margin-small-left">Anwenden</button>
            <a href="{{ route('kas-clients.create') }}" class="uk-button uk-button-secondary uk-margin-small-left">Neu</a>
        </div>

        <table class="uk-table uk-table-divider uk-table-small uk-table-hover">
            <thead>
                <tr>
                    <th><input class="uk-checkbox" type="checkbox" id="select-all"></th>
                    <th>Name</th>
                    <th>Login</th>
                    <th>Domain</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kasClients as $client)
                    <tr>
                        <td><input class="uk-checkbox" type="checkbox" name="ids[]" value="{{ $client->id }}"></td>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->login }}</td>
                        <td>{{ $client->domain }}</td>
                        <td>
                            <a href="{{ route('kas-clients.show', $client) }}" uk-icon="icon: eye"></a>
                            <a href="{{ route('kas-clients.edit', $client) }}" uk-icon="icon: pencil"></a>
                          
                            <a href="{{ route('kas-clients.impersonate.generate', $client) }}" target="_blank" uk-icon="icon: sign-in"></a>
                            
                            <form action="{{ route('kas-clients.destroy', $client) }}" method="POST" style="display:inline" onsubmit="return confirm('Wirklich löschen?');">
                                @csrf
                                @method('DELETE')
                                <a href="#" onclick="event.preventDefault(); this.closest('form').submit();" 
                                uk-icon="icon: trash" class="uk-icon-link uk-text-danger"></a>
                            </form>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </form>

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            for (const cb of checkboxes) {
                cb.checked = this.checked;
            }
        });
    </script>
@endsection
