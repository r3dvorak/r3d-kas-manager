@php
    /** @var \App\Models\Recipe|null $recipe */
    $recipe = $recipe ?? null;
    $builder = $builder ?? ['default_vars' => [], 'actions' => []];
    $defaultVars = old('default_vars');
    if (!is_array($defaultVars)) {
        $defaultVars = is_array($recipe?->variables) ? $recipe->variables : [];
    }
    $actions = old('actions');
    if (!is_array($actions)) {
        $actions = $recipe?->actions->map(fn ($a) => [
            'type' => $a->type,
            'label' => $a->label,
            'order' => $a->order,
            'params' => is_array($a->parameters) ? $a->parameters : [],
            'parameters_json' => '',
        ])->values()->all() ?? [];
    }
    if (count($actions) === 0) {
        $actions[] = ['type' => 'add_domain', 'label' => '', 'order' => 1, 'params' => [], 'parameters_json' => ''];
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
        <h4 class="uk-margin-small-bottom">Default-Variablen (Builder)</h4>
        <div class="uk-grid-small uk-child-width-1-2@m" uk-grid>
            @foreach(($builder['default_vars'] ?? []) as $key => $meta)
                <div>
                    <label class="uk-form-label">{{ $meta['label'] }}</label>
                    <input class="uk-input" name="default_vars[{{ $key }}]" value="{{ old('default_vars.' . $key, $defaultVars[$key] ?? '') }}" placeholder="{{ $meta['placeholder'] ?? '' }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="uk-width-1-1">
        <label class="uk-form-label">Erweiterte Variablen JSON (optional)</label>
        <textarea class="uk-textarea" name="variables_json" rows="4" placeholder='{"custom_key":"custom_value"}'>{{ old('variables_json', '') }}</textarea>
    </div>
</div>

<hr>
<h4 class="uk-margin-remove-top">Actions</h4>
<table class="uk-table uk-table-small uk-table-divider">
    <thead>
        <tr>
            <th style="width: 72px;">Order</th>
            <th style="width: 280px;">Type</th>
            <th style="width: 220px;">Label</th>
            <th>Parameters (Builder)</th>
            <th style="width: 260px;">Extra JSON (optional)</th>
            <th style="width: 48px;"></th>
        </tr>
    </thead>
    <tbody id="recipe-actions-body">
        @foreach($actions as $i => $action)
            <tr>
                <td><input class="uk-input" type="number" min="0" name="actions[{{ $i }}][order]" value="{{ $action['order'] ?? ($i + 1) }}"></td>
                <td>
                    <select class="uk-select action-type" name="actions[{{ $i }}][type]">
                        <option value="">-- bitte wählen --</option>
                        @foreach(($builder['actions'] ?? []) as $type => $def)
                            <option value="{{ $type }}" {{ ($action['type'] ?? '') === $type ? 'selected' : '' }}>{{ $type }} · {{ $def['label'] }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input class="uk-input" name="actions[{{ $i }}][label]" value="{{ $action['label'] ?? '' }}" placeholder="Set PHP 8.3"></td>
                <td>
                    <div class="action-params" data-index="{{ $i }}">
                        @foreach((array) ($action['params'] ?? []) as $pk => $pv)
                            <div class="uk-margin-small-bottom">
                                <label class="uk-form-label">{{ $pk }}</label>
                                <input class="uk-input" name="actions[{{ $i }}][params][{{ $pk }}]" value="{{ is_scalar($pv) ? $pv : '' }}">
                            </div>
                        @endforeach
                    </div>
                </td>
                <td><textarea class="uk-textarea" name="actions[{{ $i }}][parameters_json]" rows="2" placeholder='{"custom":"value"}'>{{ $action['parameters_json'] ?? '' }}</textarea></td>
                <td><button type="button" class="uk-button uk-button-danger uk-button-small action-remove" title="Zeile entfernen">×</button></td>
            </tr>
        @endforeach
    </tbody>
</table>
<button type="button" id="recipe-action-add" class="uk-button uk-button-default uk-button-small">Action hinzufügen</button>

<template id="recipe-action-template">
    <tr>
        <td><input class="uk-input" type="number" min="0" data-name="order"></td>
        <td>
            <select class="uk-select action-type" data-name="type">
                <option value="">-- bitte wählen --</option>
                @foreach(($builder['actions'] ?? []) as $type => $def)
                    <option value="{{ $type }}">{{ $type }} · {{ $def['label'] }}</option>
                @endforeach
            </select>
        </td>
        <td><input class="uk-input" data-name="label" placeholder="Set PHP 8.3"></td>
        <td><div class="action-params"></div></td>
        <td><textarea class="uk-textarea" data-name="parameters_json" rows="2" placeholder='{"custom":"value"}'></textarea></td>
        <td><button type="button" class="uk-button uk-button-danger uk-button-small action-remove" title="Zeile entfernen">×</button></td>
    </tr>
</template>

<script>
    (function () {
        var schemas = @json($builder['actions'] ?? []);
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
                var paramsWrap = row.querySelector('.action-params');
                if (paramsWrap) {
                    paramsWrap.querySelectorAll('input[data-param-key]').forEach(function (input) {
                        var paramKey = input.getAttribute('data-param-key');
                        input.name = 'actions[' + idx + '][params][' + paramKey + ']';
                    });
                }
            });
        };

        var renderParams = function (row) {
            var select = row.querySelector('.action-type');
            var wrap = row.querySelector('.action-params');
            if (!select || !wrap) return;

            var idx = Array.prototype.indexOf.call(body.querySelectorAll('tr'), row);
            var type = select.value || '';
            var schema = schemas[type];
            if (!schema || !schema.fields) {
                wrap.innerHTML = '';
                return;
            }

            var old = {};
            wrap.querySelectorAll('input[data-param-key]').forEach(function (input) {
                old[input.getAttribute('data-param-key')] = input.value || '';
            });

            wrap.innerHTML = '';
            Object.keys(schema.fields).forEach(function (k) {
                var meta = schema.fields[k] || {};
                var div = document.createElement('div');
                div.className = 'uk-margin-small-bottom';
                var label = document.createElement('label');
                label.className = 'uk-form-label';
                label.textContent = meta.label || k;
                var input = document.createElement('input');
                input.className = 'uk-input';
                input.setAttribute('data-param-key', k);
                input.name = 'actions[' + idx + '][params][' + k + ']';
                input.placeholder = meta.placeholder || '';
                if (typeof old[k] !== 'undefined') input.value = old[k];
                div.appendChild(label);
                div.appendChild(input);
                wrap.appendChild(div);
            });
        };

        bindRemove(body);
        body.querySelectorAll('tr').forEach(function (row) {
            var select = row.querySelector('.action-type');
            if (select) {
                select.onchange = function () { renderParams(row); renumber(); };
                renderParams(row);
            }
        });

        addBtn.onclick = function () {
            var clone = tpl.content.cloneNode(true);
            body.appendChild(clone);
            bindRemove(body);
            var rows = body.querySelectorAll('tr');
            var row = rows[rows.length - 1];
            var select = row.querySelector('.action-type');
            if (select) {
                select.onchange = function () { renderParams(row); renumber(); };
                renderParams(row);
            }
            renumber();
        };
    })();
</script>
