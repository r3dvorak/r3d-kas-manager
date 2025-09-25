@extends('layouts.app')

@section('content')
    <h1 class="uk-heading-line"><span>KAS Clients</span></h1>

    {{-- Batch Aktionen --}}
    <div class="uk-margin">
        <div class="uk-button-group">
            <button class="uk-button uk-button-default">Aktivieren</button>
            <button class="uk-button uk-button-default">Deaktivieren</button>
            <button class="uk-button uk-button-default">Archivieren</button>
            <button class="uk-button uk-button-danger">Löschen</button>
        </div>
        <div class="uk-button-group uk-margin-left">
            <button class="uk-button uk-button-primary">Exportieren</button>
            <button class="uk-button uk-button-primary">Importieren</button>
        </div>
        <a href="{{ route('kas-clients.create') }}" class="uk-button uk-button-secondary uk-margin-left">
            <span uk-icon="plus"></span> Neuer Client
        </a>
    </div>

    {{-- Tabelle --}}
    <form method="POST" action="{{ route('kas-clients.batch') }}">
        @csrf
        <table class="uk-table uk-table-divider uk-table-hover uk-table-middle uk-table-responsive">
            <thead>
                <tr>
                    <th><input class="uk-checkbox" type="checkbox" onclick="toggleAll(this)"></th>
                    <th>KAS-Name</th>
                    <th>Login</th>
                    <th>Erstellt am</th>
                    <th>Domains</th>
                    <th class="uk-text-center">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kasClients as $client)
                    <tr>
                        <td><input class="uk-checkbox" type="checkbox" name="selected[]" value="{{ $client->id }}"></td>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->api_user }}</td>
                        <td>{{ $client->created_at->format('d.m.Y') }}</td>
                        <td class="uk-text-truncate" style="max-width:300px;">
                            {{-- Domains als Komma-getrennte Liste --}}
                            {{ $client->domains->pluck('name')->join(', ') ?? '-' }}
                        </td>
                        <td class="uk-text-center">
                            <a href="{{ route('kas-clients.show', $client) }}" class="uk-icon-button" uk-icon="eye" title="Anzeigen"></a>
                            <a href="{{ route('kas-clients.edit', $client) }}" class="uk-icon-button uk-button-primary" uk-icon="pencil" title="Bearbeiten"></a>
                            <form action="{{ route('kas-clients.destroy', $client) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button class="uk-icon-button uk-button-danger" uk-icon="trash" title="Löschen"></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </form>

    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('input[name="selected[]"]');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }
    </script>
@endsection
