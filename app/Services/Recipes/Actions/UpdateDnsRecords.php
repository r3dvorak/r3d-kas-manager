<?php
/**
 * R3D KAS Manager – Recipe Action: UpdateDnsRecords
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.1
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/Actions/UpdateDnsRecords.php
 *
 * Purpose:
 *  Apply DNS records to a domain using the KAS API (action type "update_dns_records").
 *
 * Expected inputs (vars + action parameters; action wins on conflicts):
 *  - kas_login   (string)  required; falls back to $run->kas_login
 *  - domain_name (string)  required; falls back to $run->domain_name
 *  - records     (array)   optional; array of records with keys: record_type, record_name, record_data
 *
 * Returns (array):
 *  - success  (bool)
 *  - Response (array)  includes 'items' with per-record responses
 *  - error    (string) present on failure
 */

namespace App\Services\Recipes\Actions;

use App\Models\RecipeAction;
use App\Models\RecipeRun;
use App\Services\Recipes\Contracts\ActionHandler;
use App\Services\Recipes\KasGateway;
use Throwable;

class UpdateDnsRecords implements ActionHandler
{
    public function __construct(private KasGateway $kas) {}

    public function supports(string $type): bool
    {
        return $type === 'update_dns_records';
    }

    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        if ($dryRun) return ['success' => true, 'dry_run' => true];

        $login  = $vars['kas_login']   ?? $run->kas_login;
        $domain = $vars['domain_name'] ?? $run->domain_name;

        $records = $action->parameters['records'] ?? $vars['dns_records'] ?? [
            ['record_type' => 'A',   'record_name' => '',       'record_data' => '178.63.15.195'],
            ['record_type' => 'TXT', 'record_name' => '',       'record_data' => 'v=spf1 mx a ip4:178.63.15.195 -all'],
            ['record_type' => 'TXT', 'record_name' => '_dmarc', 'record_data' => 'v=DMARC1; p=quarantine; sp=quarantine; adkim=s; aspf=s'],
        ];

        $ok = true;
        $items = [];

        foreach ($records as $r) {
            $resp = $this->kas->callForLogin($login, 'add_dns_settings', [
                'zone_host'   => $domain,
                'record_type' => $r['record_type'],
                'record_name' => $r['record_name'] ?? '',
                'record_data' => $r['record_data'],
            ]);

            $ok = $ok && ($resp['success'] ?? false);
            $items[] = $resp;
        }

        return ['success' => $ok, 'Response' => ['items' => $items]];
    }
}
