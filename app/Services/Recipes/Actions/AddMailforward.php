<?php
/**
 * R3D KAS Manager – Recipe Action: AddMailforward
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.1-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/Actions/AddMailforward.php
 *
 * Purpose:
 *  Create a mail forwarder on KAS (All-Inkl) using action type "add_mailforward".
 *
 * Expected inputs (vars + action parameters; action wins on conflicts):
 *  - kas_login          (string)  required; falls back to $run->kas_login
 *  - domain_name        (string)  required; falls back to $run->domain_name
 *  - mail_forward_from  (string)  local-part or full address, e.g. "kontakt" or "kontakt@r3d.de"
 *  - mail_forward_to    (string|array) comma-separated or array of target addresses or local-parts
 *
 * Behavior:
 *  - Accepts 'mail_forward_to' as "foo@a.de,bar@b.de" or ['foo@a.de','bar@b.de'] or 'info' (local part).
 *  - Sends payload using KAS action "add_mailforward" with keys:
 *      local_part, domain_part, target_1..target_N
 *  - On success it will optionally call KasGateway sync helper if present.
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

class AddMailforward implements ActionHandler
{
    public function __construct(private KasGateway $kas) {}

    public function supports(string $type): bool
    {
        return $type === 'add_mailforward';
    }

    /**
     * Execute add_mailforward action.
     *
     * @return array Normalized response shape
     */
    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        if ($dryRun) {
            return ['success' => true, 'dry_run' => true, 'action' => 'add_mailforward'];
        }

        // Merge action parameters over recipe vars
        $p = array_merge($vars, is_array($action->parameters) ? $action->parameters : []);

        $kasLogin = $p['kas_login']   ?? $run->kas_login   ?? null;
        $domain   = $p['domain_name'] ?? $run->domain_name ?? null;

        if (!$kasLogin || !$domain) {
            return ['success' => false, 'error' => 'kas_login or domain_name missing'];
        }

        // Source address (local-part or full)
        $sourceRaw = $p['mail_forward_from'] ?? $p['from_local'] ?? null;
        if (!$sourceRaw) {
            return ['success' => false, 'error' => 'mail_forward_from missing'];
        }

        if (str_contains($sourceRaw, '@')) {
            [$srcLocal, $srcDomain] = explode('@', $sourceRaw, 2) + [null, null];
            if ($srcDomain && strcasecmp($srcDomain, $domain) !== 0) {
                return ['success' => false, 'error' => "mail_forward_from domain ({$srcDomain}) does not match domain_name ({$domain})"];
            }
            $localPart = $srcLocal;
        } else {
            $localPart = $sourceRaw;
        }

        // Targets: array or comma-separated string
        $targetsRaw = $p['mail_forward_to'] ?? $p['to_list'] ?? $p['targets'] ?? null;
        $targets = [];

        if (is_array($targetsRaw)) {
            $targets = $targetsRaw;
        } elseif (is_string($targetsRaw) && $targetsRaw !== '') {
            $parts = array_filter(array_map('trim', explode(',', $targetsRaw)));
            $targets = array_values($parts);
        }

        if (empty($targets)) {
            return ['success' => false, 'error' => 'No target address(es) provided for forward'];
        }

        // Normalize targets to full addresses
        $normalizedTargets = [];
        foreach ($targets as $t) {
            if ($t === '') continue;
            $normalizedTargets[] = str_contains($t, '@') ? $t : ($t . '@' . $domain);
        }

        // Build payload for KAS: local_part, domain_part, target_1..N
        $payload = [
            'local_part'  => $localPart,
            'domain_part' => $domain,
        ];
        $i = 1;
        foreach ($normalizedTargets as $tgt) {
            $payload['target_' . $i] = $tgt;
            $i++;
        }

        try {
            $resp = $this->kas->callForLogin($kasLogin, 'add_mailforward', $payload);
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'KAS add_mailforward error: ' . $e->getMessage()];
        }

        // Optional sync
        if (($resp['success'] ?? false) === true) {
            try {
                if (method_exists($this->kas, 'syncMailForward')) {
                    $this->kas->syncMailForward($kasLogin, $localPart, $domain);
                }
            } catch (Throwable) {
                // ignore
            }
        }

        return $resp;
    }
}
