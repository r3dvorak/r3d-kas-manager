<?php
/**
 * Artisan: kas:show-run
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.1
 * @date      2025-10-09
 * @license   MIT License
 *
 * Zeigt einen recipe_run vollständig an und exportiert optional als JSON.
 *
 * Usage:
 *  php artisan kas:show-run {id} [--export=storage/app/run_3.json]
 * 
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecipeRun;
use App\Models\RecipeActionHistory;
use Illuminate\Support\Facades\File;

class KasShowRun extends Command
{
    protected $signature = 'kas:show-run {id : recipe_run id} {--export= : optional path to export JSON}';
    protected $description = 'Show a recipe_run with its history; optionally export as prettified JSON';

    public function handle()
    {
        $id = (int) $this->argument('id');
        if (!$id) {
            $this->error('Please provide a numeric recipe_run id.');
            return 1;
        }

        $run = RecipeRun::with('recipe')->find($id);
        if (!$run) {
            $this->error("RecipeRun with id {$id} not found.");
            return 1;
        }

        // Header info
        $this->info("RecipeRun #{$run->id} — Recipe: " . ($run->recipe?->name ?? 'N/A'));
        $this->line("Status: {$run->status}   kas_login: " . ($run->kas_login ?? 'NULL') . "   domain: " . ($run->domain_name ?? 'NULL'));
        $this->line("Started: " . ($run->started_at ?? 'NULL') . "   Finished: " . ($run->finished_at ?? 'NULL'));
        $this->line("Created: {$run->created_at}   Updated: {$run->updated_at}");
        $this->line('');

        // Variables and result pretty-printed
        $this->info('Variables:');
        $vars = $run->variables;
        $this->line(json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);

        $this->info('Result:');
        $this->line(json_encode($run->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);

        // History
        $this->info("History (recipe_action_history) — entries for run {$run->id}:");
        $hist = RecipeActionHistory::where('recipe_run_id', $run->id)->orderBy('id')->get();

        if ($hist->isEmpty()) {
            $this->line("  — No history entries found.");
        } else {
            foreach ($hist as $h) {
                $this->line("-----------------------------------------------------");
                $this->line("History #{$h->id}  action: " . ($h->action_type ?? ($h->action?->type ?? 'N/A')));
                $this->line("Status: {$h->status}   started: " . ($h->started_at ?? 'NULL') . "   finished: " . ($h->finished_at ?? 'NULL'));
                $this->line("User ID: " . ($h->user_id ?? 'NULL') . "   Kas Client ID: " . ($h->kas_client_id ?? 'NULL'));
                $this->line("Kas login: " . ($h->kas_login ?? 'NULL') . "   domain: " . ($h->domain_name ?? 'NULL'));
                $this->line("");
                $this->line("Request payload:");
                $this->line(json_encode($h->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                $this->line("");
                $this->line("Response payload:");
                $this->line(json_encode($h->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                if ($h->error_message) {
                    $this->line("");
                    $this->error("Error: {$h->error_message}");
                }
                $this->line("");
            }
            $this->line("-----------------------------------------------------");
        }

        // Optional export
        $exportPath = $this->option('export');
        if ($exportPath) {
            $payload = [
                'recipe_run' => $run->toArray(),
                'history' => $hist->toArray(),
            ];

            // Resolve relative paths into base_path or storage path if needed
            $dest = $exportPath;
            if (!\Illuminate\Support\Str::startsWith($dest, ['/','\\'])) {
                // treat as relative to project root
                $dest = base_path($dest);
            }

            // ensure directory exists
            $dir = dirname($dest);
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            File::put($dest, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $this->info("Exported run + history to: {$dest}");
        }

        return 0;
    }
}
