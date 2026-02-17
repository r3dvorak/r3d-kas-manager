@php
    /** @var \App\Models\Recipe|null $recipe */
    $recipe = $recipe ?? null;
    $actions = old('actions');
    if (!is_array($actions)) {
        $actions = $recipe?->actions->map(fn ($a) => [
            'type' => $a->type,
            'label' => $a->label,
            'order' => $a->order,
            'parameters_json' => $a->parameters ? json_encode($a->parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
        ])->values()->all() ?? [];
    }
    if (count($actions) === 0) {
        $actions[] = ['type' => '', 'label' => '', 'order' => 1, 'parameters_json' => ''];
    }
@endphp

<div class="uk-grid-small" uk-grid>
    <div class="uk-width-1-2@m">
        <label class="uk-form-label">Name</label>
        <input class="uk-input" name="name" value="{{ old('name', $recipe?->name) }}" required>
    </div>
    <div class="uk-width-1-4@m">
        <label class="uk-form-label">Status</label>
        @php($status = old('status', $recipe?->status ?? 'draft'))
        <select class="uk-select" name="status">
            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>draft</option>
            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>active</option>
            <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>archived</option>
        </select>
    </div>
    <div class="uk-width-1-4@m">
        <label class="uk-form-label">Kategorie</label>
        <input class="uk-input" name="category" value="{{ old('category', $recipe?->category) }}" placeholder="dns|mail|domain|composite">
    </div>
    <div class="uk-width-1-1">
        <label class="uk-form-label">Beschreibung</label>
        <textarea class="uk-textarea" name="description" rows="2">{{ old('description', $recipe?->description) }}</textarea>
    </div>
    <div class="uk-width-1-1">
        <label><input type="checkbox" class="uk-checkbox" name="is_template" value="1" {{ old('is_template', (int) ($recipe?->is_template ?? 0)) ? 'checked' : '' }}> Template</label>
    </div>
    <div class="uk-width-1-1">
        <label class="uk-form-label">Default-Variablen (JSON)</label>
        <textarea class="uk-textarea" name="variables_json" rows="5" placeholder='{"kas_login":"w01...","php_version":"8.3"}'>{{ old('variables_json', $recipe?->variables ? json_encode($recipe->variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
    </div>
</div>

<hr>
<h4 class="uk-margin-remove-top">Actions</h4>
<table class="uk-table uk-table-small uk-table-divider">
    <thead>
        <tr>
            <th style="width: 72px;">Order</th>
            <th style="width: 220px;">Type</th>
            <th style="width: 220px;">Label</th>
            <th>Parameters (JSON)</th>
            <th style="width: 48px;"></th>
        </tr>
    </thead>
    <tbody id="recipe-actions-body">
        @foreach($actions as $i => $action)
            <tr>
                <td><input class="uk-input" type="number" min="0" name="actions[{{ $i }}][order]" value="{{ $action['order'] ?? ($i + 1) }}"></td>
                <td><input class="uk-input" name="actions[{{ $i }}][type]" value="{{ $action['type'] ?? '' }}" placeholder="set_php_version"></td>
                <td><input class="uk-input" name="actions[{{ $i }}][label]" value="{{ $action['label'] ?? '' }}" placeholder="Set PHP 8.3"></td>
                <td><textarea class="uk-textarea" name="actions[{{ $i }}][parameters_json]" rows="2" placeholder='{"version":"8.3"}'>{{ $action['parameters_json'] ?? '' }}</textarea></td>
                <td><button type="button" class="uk-button uk-button-danger uk-button-small action-remove" title="Zeile entfernen">×</button></td>
            </tr>
        @endforeach
    </tbody>
</table>
<button type="button" id="recipe-action-add" class="uk-button uk-button-default uk-button-small">Action hinzufügen</button>

<template id="recipe-action-template">
    <tr>
        <td><input class="uk-input" type="number" min="0" data-name="order"></td>
        <td><input class="uk-input" data-name="type" placeholder="set_php_version"></td>
        <td><input class="uk-input" data-name="label" placeholder="Set PHP 8.3"></td>
        <td><textarea class="uk-textarea" data-name="parameters_json" rows="2" placeholder='{"version":"8.3"}'></textarea></td>
        <td><button type="button" class="uk-button uk-button-danger uk-button-small action-remove" title="Zeile entfernen">×</button></td>
    </tr>
</template>

<script>
    (function () {
        var body = document.getElementById('recipe-actions-body');
        var addBtn = document.getElementById('recipe-action-add');
        var tpl = document.getElementById('recipe-action-template');
        if (!body || !addBtn || !tpl) return;

        var bindRemove = function (scope) {
            scope.querySelectorAll('.action-remove').forEach(function (btn) {
                btn.onclick = function () {
                    var row = btn.closest('tr');
                    if (row) row.remove();
                };
            });
        };

        var renumber = function () {
            body.querySelectorAll('tr').forEach(function (row, idx) {
                row.querySelectorAll('[data-name], input[name], textarea[name]').forEach(function (field) {
                    var key = field.getAttribute('data-name');
                    if (!key && field.name) {
                        var match = field.name.match(/\]\[(.+)\]$/);
                        key = match ? match[1] : null;
                    }
                    if (!key) return;
                    field.name = 'actions[' + idx + '][' + key + ']';
                    if (key === 'order' && (field.value === '' || field.value === '0')) {
                        field.value = String(idx + 1);
                    }
                });
            });
        };

        bindRemove(body);

        addBtn.onclick = function () {
            var clone = tpl.content.cloneNode(true);
            body.appendChild(clone);
            bindRemove(body);
            renumber();
        };
    })();
</script>

