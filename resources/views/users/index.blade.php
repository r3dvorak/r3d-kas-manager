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
    <h1 class="uk-heading-line"><span>User Management</span></h1>

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

    <form action="{{ route('users.batch') }}" method="POST">
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
            <a href="{{ route('users.create') }}" class="uk-button uk-button-secondary uk-margin-small-left">Neu</a>
        </div>

        <table class="uk-table uk-table-divider uk-table-small uk-table-hover">
            <thead>
                <tr>
                    <th><input class="uk-checkbox" type="checkbox" id="select-all"></th>
                    <th>Name</th>
                    <th>Login</th>
                    <th>Email</th>
                    <th>Rolle</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td><input class="uk-checkbox" type="checkbox" name="ids[]" value="{{ $user->id }}"></td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->login }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role }}</td>
                        <td>
                            <a href="{{ route('users.show', $user) }}" uk-icon="icon: eye"></a>
                            <a href="{{ route('users.edit', $user) }}" uk-icon="icon: pencil"></a>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline" onsubmit="return confirm('Wirklich löschen?');">
                                @csrf
                                @method('DELETE')
                                <a href="#" onclick="event.preventDefault(); this.closest('form').submit();" uk-icon="icon: trash" class="uk-icon-link uk-text-danger"></a>
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
