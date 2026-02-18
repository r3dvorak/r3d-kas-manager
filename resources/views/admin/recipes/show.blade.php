@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Recipe: {{ $recipe->name }}</span></h1>

@if(session('ok'))
    <div class="uk-alert-success" uk-alert>{{ session('ok') }}</div>
@endif

@if(session('wizard_credentials'))
    @php($wiz = session('wizard_credentials'))
    <div class="uk-alert-warning" uk-alert>
        <p class="uk-margin-small-bottom"><strong>Wizard-Ergebnis (einmalige Anzeige)</strong></p>
        @if(!empty($wiz['mailboxes']))
            <p class="uk-margin-small-bottom"><strong>Mailbox-Zugangsdaten:</strong></p>
            <ul class="uk-margin-small-top">
                @foreach($wiz['mailboxes'] as $mb)
                    <li><code>{{ $mb['email'] }}</code> / <code>{{ $mb['password'] }}</code></li>
                @endforeach
            </ul>
        @endif
        @if(!empty($wiz['forwards']))
            <p class="uk-margin-small-bottom"><strong>Weiterleitungen:</strong></p>
            <ul class="uk-margin-small-top">
                @foreach($wiz['forwards'] as $fw)
                    <li><code>{{ $fw['from'] }}</code> → <code>{{ $fw['to'] }}</code></li>
                @endforeach
            </ul>
        @endif
        @if(!empty($wiz['notes']))
            <p class="uk-margin-small-bottom"><strong>Hinweise:</strong></p>
            <ul class="uk-margin-small-top">
                @foreach($wiz['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

<div class="uk-grid-medium" uk-grid>
    <div class="uk-width-2-3@m">
        <div class="uk-card uk-card-default uk-card-body">
            <h3 class="uk-card-title uk-margin-remove-bottom">Definition</h3>
            <div class="uk-text-meta">Status: {{ $recipe->status ?? 'draft' }} · Version: {{ $recipe->version }} · Kategorie: {{ $recipe->category ?: '—' }}</div>
            <p class="uk-margin-small-top">{{ $recipe->description ?: '—' }}</p>

            <h4>Actions</h4>
            <table class="uk-table uk-table-small uk-table-divider">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Type</th>
                        <th>Label</th>
                        <th>Parameters</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipe->actions as $action)
                        <tr>
                            <td>{{ $action->order }}</td>
                            <td><code>{{ $action->type }}</code></td>
                            <td>{{ $action->label ?: '—' }}</td>
                            <td><pre class="uk-margin-remove">{{ $action->parameters ? json_encode($action->parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{}' }}</pre></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="uk-text-muted">Keine Actions definiert.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if($recipe->variables)
                <h4>Default-Variablen</h4>
                <pre>{{ json_encode($recipe->variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </div>
    </div>

    <div class="uk-width-1-3@m">
        <div class="uk-card uk-card-default uk-card-body">
            <h4 class="uk-margin-top-remove">Aktionen</h4>
            <p class="uk-text-small uk-text-muted">Run-Parameter überschreiben Default-Variablen.</p>
            <form action="{{ route_w('admin.recipes.run-dry', $recipe) }}" method="POST" class="uk-margin-small-bottom">
                @csrf
                <label class="uk-form-label">KAS Login</label>
                <input class="uk-input" name="kas_login" placeholder="w01xxxx">
                <label class="uk-form-label uk-margin-small-top">Domain</label>
                <input class="uk-input" name="domain_name" placeholder="example.tld">
                <label class="uk-form-label uk-margin-small-top">Variablen JSON</label>
                <textarea class="uk-textarea" name="variables_json" rows="4" placeholder='{"php_version":"8.3"}'></textarea>
                <button type="submit" class="uk-button uk-button-default uk-width-1-1 uk-margin-small-top">Dry-Run</button>
            </form>

            <form action="{{ route_w('admin.recipes.run-apply', $recipe) }}" method="POST" onsubmit="return confirm('Recipe jetzt wirklich ausführen?');">
                @csrf
                <label class="uk-form-label">KAS Login</label>
                <input class="uk-input" name="kas_login" placeholder="w01xxxx">
                <label class="uk-form-label uk-margin-small-top">Domain</label>
                <input class="uk-input" name="domain_name" placeholder="example.tld">
                <label class="uk-form-label uk-margin-small-top">Variablen JSON</label>
                <textarea class="uk-textarea" name="variables_json" rows="4" placeholder='{"php_version":"8.3"}'></textarea>
                <button type="submit" class="uk-button uk-button-primary uk-width-1-1 uk-margin-small-top">Apply</button>
            </form>

            <hr>
            <a href="{{ route_w('admin.recipes.edit', $recipe) }}" class="uk-button uk-button-default uk-width-1-1">Bearbeiten</a>
            <form action="{{ route_w('admin.recipes.destroy', $recipe) }}" method="POST" class="uk-margin-small-top">
                @csrf @method('DELETE')
                <button class="uk-button uk-button-danger uk-width-1-1" type="submit" onclick="return confirmDeleteTwice('Recipe wirklich löschen?', 'LOESCHEN');">Löschen</button>
            </form>
        </div>
    </div>
</div>

<div class="uk-card uk-card-default uk-card-body uk-margin-top">
    <h3 class="uk-card-title">Letzte Runs</h3>
    <table class="uk-table uk-table-small uk-table-divider">
        <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>KAS Login</th>
                <th>Domain</th>
                <th>Start</th>
                <th>Ende</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recipe->runs as $run)
                <tr>
                    <td>#{{ $run->id }}</td>
                    <td>{{ $run->status }}</td>
                    <td>{{ $run->kas_login ?: '—' }}</td>
                    <td>{{ $run->domain_name ?: '—' }}</td>
                    <td>{{ optional($run->started_at)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ optional($run->finished_at)->format('Y-m-d H:i:s') }}</td>
                    <td><a href="{{ route_w('admin.recipes.runs.show', [$recipe, $run]) }}">Öffnen</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="uk-text-muted">Noch keine Runs vorhanden.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
