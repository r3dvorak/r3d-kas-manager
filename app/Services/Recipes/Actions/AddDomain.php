<?php
/**
 * R3D KAS Manager – Recipe Action: AddDomain
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.9-alpha
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
use App\Models\KasClient;
use App\Services\Recipes\KasGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDomain
{
    public function supportedTypes(): array { return ['add_domain']; }

    public function supports(string $type): bool
    {
        return strtolower(preg_replace('/[^a-z0-9]/','',$type)) === 'adddomain';
    }

    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        // Normalize domain values
        $full = $vars['domain'] ?? $vars['domain_name'] ?? $run->domain_name ?? null;
        $name = $vars['domain_name'] ?? null;
        $tld  = $vars['domain_tld']  ?? null;

        // Split full domain if needed
        if ($full && strpos($full, '.') !== false) {
            [$n, $t] = explode('.', $full, 2);
            $name = $n;
            $tld  = $t;
        }

        if (!$name || !$tld) {
            return ['success' => false, 'error' => 'missing_domain_name_or_tld'];
        }

        $kasLogin = $vars['kas_login'] ?? $run->kas_login ?? null;
        if (!$kasLogin) {
            return ['success' => false, 'error' => 'missing_kas_login'];
        }

        $fqdn = "{$name}.{$tld}";

        if ($dryRun) {
            return [
                'success' => true,
                'dry_run' => true,
                'action'  => 'add_domain',
                'params'  => [
                    'domain_name' => $name,
                    'domain_tld'  => $tld,
                    'kas_login'   => $kasLogin,
                ],
            ];
        }

        /** @var KasGateway $gw */
        $gw = app(KasGateway::class);
        $resp = $gw->callForLogin($kasLogin, 'add_domain', [
            'domain_name' => $name,
            'domain_tld'  => $tld,
        ]);

        if (!($resp['success'] ?? false)) {
            return [
                'success'  => false,
                'error'    => $resp['error'] ?? 'unknown_error',
                'response' => $resp,
            ];
        }

        // Mirror into kas_domains (by domain_full)
        try {
            $cols = Schema::getColumnListing('kas_domains');
            $hasFull = in_array('domain_full', $cols, true);
            $hasName = in_array('domain_name', $cols, true);
            $hasTld  = in_array('domain_tld',  $cols, true);
            $hasCid  = in_array('kas_client_id', $cols, true);
            $now     = now();

            if ($hasFull && $hasName) {
                $cid = $hasCid
                    ? KasClient::where('account_login', $kasLogin)->value('id')
                    : null;

                $unique = $hasCid
                    ? ['kas_client_id' => $cid, 'domain_full' => $fqdn]
                    : ['domain_full' => $fqdn];

                $update = [
                    'domain_name' => $name,
                    'domain_tld'  => $tld,
                    'domain_full' => $fqdn,
                    'is_active'   => 'Y',
                    'in_progress' => 'N',
                    'dummy_host'  => 'N',
                    'ssl_proxy'   => 'N',
                    'ssl_certificate_ip'  => 'N',
                    'ssl_certificate_sni' => 'N',
                    'fpse_active' => 'N',
                    'domain_redirect_status' => 0,
                    'php_deprecated' => 'N',
                    'php_version' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ];

                DB::table('kas_domains')->updateOrInsert($unique, $update);
            }
        } catch (\Throwable $e) {
            return [
                'success' => true,
                'action'  => 'add_domain',
                'details' => $resp,
                'warning' => 'local_kas_domains_upsert_failed: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'action'  => 'add_domain',
            'details' => $resp,
        ];
    }
}
