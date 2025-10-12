<?php
/**
 * R3D KAS Manager – Recipe Action: AddDomain
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.0-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/Actions/AddDomain.php
 *
 * Purpose:
 *  Create a domain on KAS (All-Inkl) using action type "add_domain".
 *
 * Expected inputs (vars + action parameters; action wins on conflicts):
 *  - kas_login   (string)  required; falls back to $run->kas_login
 *  - domain_name (string)  required; falls back to $run->domain_name
 *  - php_version (string)  optional; action param or recipe var or default "8.3"
 *
 * Returns (array):
 *  - success  (bool)
 *  - Response (mixed)    raw/normalized KAS response when available
 *  - error    (string?)  present on failure
 */

namespace App\Services\Recipes\Actions;

use App\Models\RecipeAction;
use App\Models\RecipeRun;
use App\Services\Recipes\Contracts\ActionHandler;
use App\Services\Recipes\KasGateway;
use Throwable;

class AddDomain implements ActionHandler
{
    public function __construct(private KasGateway $kas) {}

    public function supports(string $type): bool
    {
        return $type === 'add_domain';
    }

    /**
     * Execute add_domain action.
     *
     * @param RecipeAction $action
     * @param RecipeRun    $run
     * @param array        $vars
     * @param bool         $dryRun
     * @return array
     */
    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        if ($dryRun) {
            return ['success' => true, 'dry_run' => true, 'action' => 'add_domain'];
        }

        // Resolve kas login and domain name (action params override vars)
        $p = array_merge($vars, is_array($action->parameters) ? $action->parameters : []);

        $kasLogin = $p['kas_login']   ?? $run->kas_login   ?? null;
        $domain   = $p['domain_name'] ?? $run->domain_name ?? null;
        $phpVer   = $p['php_version'] ?? $p['php'] ?? '8.3';

        if (!$kasLogin) {
            return ['success' => false, 'error' => 'kas_login missing'];
        }
        if (!$domain) {
            return ['success' => false, 'error' => 'domain_name missing'];
        }

        $payload = [
            'domain'      => $domain,
            'php_version' => (string)$phpVer,
        ];

        try {
            // Use convenience callForLogin which resolves KasClient and password
            $resp = $this->kas->callForLogin($kasLogin, 'add_domain', $payload);
            return $resp;
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'KAS add_domain error: ' . $e->getMessage()];
        }
    }
}
