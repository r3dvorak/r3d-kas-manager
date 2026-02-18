@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Recipes (Admin)</span></h1>

@if(session('ok'))
    <div class="uk-alert-success" uk-alert>{{ session('ok') }}</div>
@endif

<div class="uk-flex uk-flex-between uk-flex-middle uk-margin-small-bottom">
    <form method="GET" action="{{ route_w('admin.recipes.index') }}" class="uk-search uk-search-default">
        <span uk-search-icon></span>
        <input class="uk-search-input" type="search" name="q" value="{{ $q ?? '' }}" placeholder="Suche nach Name/Kategorie">
    </form>
    <div class="uk-flex uk-grid-small" uk-grid>
        <div><a href="{{ route_w('admin.recipes.wizard') }}" class="uk-button uk-button-secondary">Wizard</a></div>
        <div><a href="{{ route_w('admin.recipes.create') }}" class="uk-button uk-button-primary">NEU</a></div>
    </div>
</div>

<table class="uk-table uk-table-divider uk-table-small uk-table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Kategorie</th>
            <th>Version</th>
            <th class="uk-text-right">Actions</th>
            <th class="uk-text-right">Runs</th>
            <th class="uk-text-nowrap">Aktualisiert</th>
            <th class="uk-text-nowrap">Aktionen</th>
        </tr>
    </thead>
    <tbody>
        @forelse($recipes as $recipe)
            <tr>
                <td><a href="{{ route_w('admin.recipes.show', $recipe) }}">{{ $recipe->name }}</a></td>
                <td><span class="uk-label">{{ $recipe->status ?? 'draft' }}</span></td>
                <td>{{ $recipe->category ?: '—' }}</td>
                <td>{{ $recipe->version }}</td>
                <td class="uk-text-right">{{ $recipe->actions_count }}</td>
                <td class="uk-text-right">{{ $recipe->runs_count }}</td>
                <td>{{ optional($recipe->updated_at)->format('Y-m-d H:i') }}</td>
                <td class="uk-text-nowrap">
                    <a href="{{ route_w('admin.recipes.edit', $recipe) }}" uk-icon="pencil" title="Bearbeiten"></a>
                    <form action="{{ route_w('admin.recipes.destroy', $recipe) }}" method="POST" class="uk-display-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="uk-button uk-button-link uk-text-danger" uk-icon="trash" title="Löschen" onclick="return confirmDeleteTwice('Recipe wirklich löschen?', 'LOESCHEN');"></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="uk-text-muted">Noch keine Recipes vorhanden.</td></tr>
        @endforelse
    </tbody>
</table>

{{ $recipes->links() }}
@endsection
