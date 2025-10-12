<?php
/**
 * R3D KAS Manager – Recipe Action: AddMailaccount
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.1-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/Actions/AddMailaccount.php
 *
 * Purpose:
 *  Create a mailbox on KAS (All-Inkl) using action type "add_mailaccount".
 *
 * Expected inputs (vars + action parameters; action wins on conflicts):
 *  - kas_login         (string)  required; falls back to $run->kas_login
 *  - domain_name       (string)  required; falls back to $run->domain_name
 *  - mail_account      (string)  local-part, e.g. "info"      [default: "info"]
 *  - mail_password     (string)  mailbox password             [default: "ChangeMe123!"]
 *  - mail_quota_mb     (int)     optional; KAS quota_rule (MB)
 *  - webmail_autologin (bool|'Y'|'N') optional; coerced to 'Y'/'N'
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

class AddMailaccount implements ActionHandler
{
    public function __construct(private KasGateway $kas) {}

    public function supports(string $type): bool
    {
        return $type === 'add_mailaccount';
    }

    /**
     * Execute add_mailaccount.
     *
     * @return array Normalized KAS-style response: ['success'=>bool, 'Response'=>... ] or ['success'=>false,'error'=>...]
     */
    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        if ($dryRun) {
            return [
                'success' => true,
                'dry_run' => true,
                'action'  => 'add_mailaccount',
            ];
        }

        // merge action parameters over recipe vars
        $p = array_merge($vars, is_array($action->parameters) ? $action->parameters : []);

        $kasLogin = $p['kas_login']   ?? $run->kas_login   ?? null;
        $domain   = $p['domain_name'] ?? $run->domain_name ?? null;

        if (!$kasLogin || !$domain) {
            return ['success' => false, 'error' => 'kas_login or domain_name missing'];
        }

        $local    = $p['mail_account'] ?? $p['local_part'] ?? 'info';
        $password = $p['mail_password'] ?? $p['default_password'] ?? 'ChangeMe123!';
        $quotaMb  = $p['mail_quota_mb'] ?? $p['quota_mb'] ?? null;
        $autolog  = $p['webmail_autologin'] ?? null;

        // Build KAS payload using the common (local_part/domain_part) keys
        $payload = [
            'local_part'    => $local,
            'domain_part'   => $domain,
            'mail_password' => $password,
        ];

        if ($quotaMb !== null) {
            $payload['quota_rule'] = (int) $quotaMb;
        }
        if ($autolog !== null) {
            $payload['webmail_autologin'] = ($autolog === true || $autolog === 'Y') ? 'Y' : 'N';
        }

        try {
            // callForLogin resolves the KasClient/pw and matches KasGateway signature -> analyzer happy
            $resp = $this->kas->callForLogin($kasLogin, 'add_mailaccount', $payload);
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'KAS add_mailaccount error: ' . $e->getMessage()];
        }

        // Best-effort: optional local sync if gateway implements it (guarded)
        if (($resp['success'] ?? false) === true) {
            try {
                if (method_exists($this->kas, 'syncMailAccount')) {
                    $this->kas->syncMailAccount($kasLogin, $local, $domain);
                }
            } catch (Throwable) {
                // ignore sync errors; creation succeeded
            }
        }

        return $resp;
    }
}
