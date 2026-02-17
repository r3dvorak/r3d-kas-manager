<?php
/**
 * R3D KAS Manager - Admin Recipe Controller
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.31.2
 * @date      2026-02-17
 * @license   MIT License
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeRun;
use App\Services\RecipeExecutor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $recipes = Recipe::query()
            ->withCount(['actions', 'runs'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('description', 'like', '%' . $q . '%')
                    ->orWhere('category', 'like', '%' . $q . '%');
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.recipes.index', compact('recipes', 'q'));
    }

    public function create(): View
    {
        return view('admin.recipes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        [$recipeData, $actions] = $this->validatedRecipePayload($request);

        DB::transaction(function () use ($recipeData, $actions): void {
            $recipe = Recipe::create($recipeData);
            $this->syncActions($recipe, $actions);
        });

        return redirect()->route('admin.recipes.index')->with('ok', 'Recipe erstellt.');
    }

    public function show(Recipe $recipe): View
    {
        $recipe->load(['actions', 'runs' => fn ($q) => $q->orderByDesc('id')->limit(20)]);

        return view('admin.recipes.show', compact('recipe'));
    }

    public function edit(Recipe $recipe): View
    {
        $recipe->load('actions');

        return view('admin.recipes.edit', compact('recipe'));
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        [$recipeData, $actions] = $this->validatedRecipePayload($request);

        DB::transaction(function () use ($recipe, $recipeData, $actions): void {
            $recipeData['version'] = (int) $recipe->version + 1;
            $recipe->update($recipeData);
            $this->syncActions($recipe, $actions);
        });

        return redirect()->route('admin.recipes.show', $recipe)->with('ok', 'Recipe aktualisiert.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return redirect()->route('admin.recipes.index')->with('ok', 'Recipe gelöscht.');
    }

    public function runDry(Request $request, Recipe $recipe, RecipeExecutor $executor): RedirectResponse
    {
        $vars = $this->validatedRunVars($request);
        $run = $executor->executeRecipe($recipe->load('actions'), $vars, auth('web')->user(), ['dryrun' => true]);

        return redirect()->route('admin.recipes.runs.show', [$recipe, $run])->with('ok', 'Dry-Run gestartet.');
    }

    public function runApply(Request $request, Recipe $recipe, RecipeExecutor $executor): RedirectResponse
    {
        $vars = $this->validatedRunVars($request);
        $run = $executor->executeRecipe($recipe->load('actions'), $vars, auth('web')->user(), ['dryrun' => false]);

        return redirect()->route('admin.recipes.runs.show', [$recipe, $run])->with('ok', 'Recipe ausgeführt.');
    }

    public function showRun(Recipe $recipe, RecipeRun $run): View
    {
        abort_unless((int) $run->recipe_id === (int) $recipe->id, 404);

        $run->load(['history' => fn ($q) => $q->orderBy('id')]);

        return view('admin.recipes.run', compact('recipe', 'run'));
    }

    private function validatedRecipePayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:draft,active,archived'],
            'is_template' => ['nullable', 'boolean'],
            'variables_json' => ['nullable', 'json'],
            'actions' => ['nullable', 'array'],
            'actions.*.type' => ['nullable', 'string', 'max:255'],
            'actions.*.label' => ['nullable', 'string', 'max:255'],
            'actions.*.order' => ['nullable', 'integer', 'min:0'],
            'actions.*.parameters_json' => ['nullable', 'json'],
        ]);

        $variables = null;
        if (!empty($validated['variables_json'])) {
            $variables = json_decode((string) $validated['variables_json'], true);
        }

        $recipeData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'],
            'is_template' => (bool) ($validated['is_template'] ?? false),
            'variables' => $variables,
        ];

        $actions = [];
        foreach ((array) ($validated['actions'] ?? []) as $row) {
            $type = trim((string) ($row['type'] ?? ''));
            if ($type === '') {
                continue;
            }

            $params = null;
            if (!empty($row['parameters_json'])) {
                $params = json_decode((string) $row['parameters_json'], true);
            }

            $actions[] = [
                'type' => $type,
                'label' => ($row['label'] ?? null) ?: null,
                'order' => (int) ($row['order'] ?? 0),
                'parameters' => $params,
            ];
        }

        usort($actions, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        return [$recipeData, $actions];
    }

    private function validatedRunVars(Request $request): array
    {
        $validated = $request->validate([
            'kas_login' => ['nullable', 'string', 'max:64'],
            'domain_name' => ['nullable', 'string', 'max:255'],
            'variables_json' => ['nullable', 'json'],
        ]);

        $extra = [];
        if (!empty($validated['variables_json'])) {
            $extra = (array) json_decode((string) $validated['variables_json'], true);
        }

        $vars = [
            'kas_login' => trim((string) ($validated['kas_login'] ?? '')),
            'domain_name' => trim((string) ($validated['domain_name'] ?? '')),
        ];

        return array_filter(array_merge($extra, $vars), static fn ($v) => $v !== '' && $v !== null);
    }

    private function syncActions(Recipe $recipe, array $actions): void
    {
        $recipe->actions()->delete();
        foreach ($actions as $idx => $action) {
            $recipe->actions()->create([
                'type' => $action['type'],
                'label' => $action['label'],
                'order' => $action['order'] ?: ($idx + 1),
                'parameters' => $action['parameters'],
            ]);
        }
    }
}

