<?php
/**
 * R3D KAS Manager – Recipe Action: AddDomain
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.8-alpha
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
use App\Services\Recipes\KasGateway;

class AddDomain
{
    public function supportedTypes(): array
    {
        return ['add_domain'];
    }

    public function supports(string $type): bool
    {
        $t = strtolower(preg_replace('/[^a-z0-9]/', '', $type));
        return $t === 'adddomain';
    }

    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        // Accept domain in one piece or split pair
        $full = $vars['domain'] ?? $vars['domain_name'] ?? $run->domain_name ?? null;
        $name = $vars['domain_name'] ?? null;
        $tld  = $vars['domain_tld']  ?? null;

        if ($full && (!$name || !$tld)) {
            // Split once on the first dot (handles subdomains intentionally not here)
            if (strpos($full, '.') !== false) {
                [$n, $t] = explode('.', $full, 2);
                $name = $name ?? $n;
                $tld  = $tld  ?? $t;
            }
        }

        if (!$name || !$tld) {
            return ['success' => false, 'error' => 'missing_domain_name_or_tld'];
        }

        $kasLogin = $vars['kas_login'] ?? $run->kas_login ?? null;
        if (!$kasLogin) {
            return ['success' => false, 'error' => 'missing_kas_login'];
        }

        if ($dryRun) {
            return [
                'success' => true,
                'dry_run' => true,
                'action'  => 'add_domain',
                'params'  => ['domain_name' => $name, 'domain_tld' => $tld, 'kas_login' => $kasLogin],
            ];
        }

        /** @var KasGateway $gw */
        $gw = app(KasGateway::class);

        // IMPORTANT: pass split keys exactly as KAS expects
        $resp = $gw->callForLogin($kasLogin, 'add_domain', [
            'domain_name' => $name,
            'domain_tld'  => $tld,
        ]);

        if (!($resp['success'] ?? false)) {
            return ['success' => false, 'error' => $resp['error'] ?? 'unknown_error', 'response' => $resp];
        }

        return [
            'success' => true,
            'action'  => 'add_domain',
            'details' => $resp,
        ];
    }
}
