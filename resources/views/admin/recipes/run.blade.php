@extends('layouts.app')

@section('content')
<h1 class="uk-heading-line"><span>Recipe Run #{{ $run->id }}</span></h1>

<div class="uk-card uk-card-default uk-card-body uk-margin-bottom">
    <div><strong>Recipe:</strong> <a href="{{ route_w('admin.recipes.show', $recipe) }}">{{ $recipe->name }}</a></div>
    <div><strong>Status:</strong> {{ $run->status }}</div>
    <div><strong>KAS Login:</strong> {{ $run->kas_login ?: '—' }}</div>
    <div><strong>Domain:</strong> {{ $run->domain_name ?: '—' }}</div>
    <div><strong>Start:</strong> {{ optional($run->started_at)->format('Y-m-d H:i:s') ?: '—' }}</div>
    <div><strong>Ende:</strong> {{ optional($run->finished_at)->format('Y-m-d H:i:s') ?: '—' }}</div>
    @if($run->variables)
        <div class="uk-margin-top">
            <strong>Run-Variablen</strong>
            <pre>{{ json_encode($run->variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>

<div class="uk-card uk-card-default uk-card-body">
    <h3 class="uk-card-title">Action-History</h3>
    <table class="uk-table uk-table-small uk-table-divider">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Status</th>
                <th>Fehler</th>
                <th>Payloads</th>
            </tr>
        </thead>
        <tbody>
            @forelse($run->history as $h)
                <tr>
                    <td>#{{ $h->id }}</td>
                    <td><code>{{ $h->action_type }}</code></td>
                    <td>{{ $h->status }}</td>
                    <td>{{ $h->error_message ?: '—' }}</td>
                    <td>
                        <details>
                            <summary>request/response</summary>
                            <div class="uk-grid-small uk-child-width-1-2@m" uk-grid>
                                <div>
                                    <strong>request</strong>
                                    <pre>{{ $h->request_payload ? json_encode($h->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{}' }}</pre>
                                </div>
                                <div>
                                    <strong>response</strong>
                                    <pre>{{ $h->response_payload ? json_encode($h->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{}' }}</pre>
                                </div>
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="uk-text-muted">Keine History-Einträge vorhanden.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

