<?php
/**
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.2-alpha
 * @date      2025-10-10
 * @license   MIT License
 *
 * app\Jobs\PostPropagationRunner.php
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use App\Models\Recipe;

class PostPropagationRunner implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected string $domain;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    public function handle(): void
    {
        // find a stage-2 recipe (by name). Adjust name if you used different seed.
        $recipe = Recipe::where('name', 'Post-propagation: DNS + SSL + Mail')->first();
        if (!$recipe) {
            \Log::warning("PostPropagationRunner: stage-2 recipe not found.");
            return;
        }

        // Use Artisan command kas:apply-recipe to avoid coupling to Executor signature.
        // We don't pass --dryrun; this will execute for real.
        $params = [
            'recipe_id' => $recipe->id,
            '--domain' => $this->domain,
            // kas_login will be determined inside kas:apply-recipe from kas_domains/owner or you can pass it here
        ];

        // If kas_login is required, you can add it here, e.g.:
        // $params['--kas_login'] = 'w01e77bc';

        Artisan::call('kas:apply-recipe', $params);
        \Log::info("PostPropagationRunner: dispatched kas:apply-recipe for {$this->domain} (recipe {$recipe->id}).");
    }
}
