<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.2.4-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Artisan command to list recipe runs (simulated or real).
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecipeRun;

class RecipeRuns extends Command
{
    protected $signature = 'recipe:runs {--limit=10 : Number of runs to show}';

    protected $description = 'List past recipe runs with status, variables and timestamp';

    public function handle()
    {
        $limit = (int) $this->option('limit');

        $runs = RecipeRun::with('recipe')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($runs->isEmpty()) {
            $this->warn('No recipe runs found.');
            return 0;
        }

        $this->table(
            ['ID', 'Recipe', 'Status', 'Variables', 'Created'],
            $runs->map(function ($run) {
                return [
                    $run->id,
                    $run->recipe->name ?? 'N/A',
                    $run->status,
                    json_encode($run->variables),
                    $run->created_at->toDateTimeString(),
                ];
            })->toArray()
        );

        return 0;
    }
}
