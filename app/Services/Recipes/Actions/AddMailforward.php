<?php
/**
 * R3D KAS Manager – Recipe Action: AddMailforward
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.6-alpha
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

    /**
     * Accept both naming variants to be robust:
     * - add_mailforward
     * - add_mail_forward
     */
    public function supports(string $type): bool
    {
        return in_array($type, ['add_mailforward', 'add_mail_forward'], true);
    }

    /**
     * Handle creation of a mail forward.
     *
     * Expected parameters (action.parameters or run/vars):
     *  - kas_login
     *  - domain_name
     *  - mail_forward_from (local part)
     *  - mail_forward_to   (target local part or full address)
     *
     * Handler normalizes to KAS expected payload.
     */
    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        if ($dryRun) {
            return ['success' => true, 'dry_run' => true, 'action' => 'add_mail_forward'];
        }

        // action->parameters may be stored as JSON string
        $actionParams = is_array($action->parameters)
            ? $action->parameters
            : (is_string($action->parameters) ? json_decode($action->parameters, true) ?? [] : []);

        // Vars precedence: $vars already merged by executor (recipe defaults + runtime + action params merged)
        $merged = array_merge($vars, $actionParams);

        $kasLogin = $merged['kas_login'] ?? $run->kas_login ?? null;
        $domain   = $merged['domain_name'] ?? $run->domain_name ?? null;

        if (!$kasLogin || !$domain) {
            return ['success' => false, 'error' => 'kas_login or domain_name missing'];
        }

        // compute local parts / addresses
        $fromLocal = $merged['mail_forward_from'] ?? $merged['mail_forward_address'] ?? null;
        $toTarget  = $merged['mail_forward_to'] ?? $merged['mail_forward_targets'] ?? null;

        // allow either full address or local part; normalize to full addresses
        $fromAddr = strpos(($fromLocal ?? ''), '@') === false ? ($fromLocal . '@' . $domain) : $fromLocal;
        $toAddr   = strpos(($toTarget ?? ''), '@') === false ? ($toTarget . '@' . $domain) : $toTarget;

        if (!$fromAddr || !$toAddr) {
            return ['success' => false, 'error' => 'mail_forward_from or mail_forward_to missing'];
        }

        try {
            $payload = [
                'mail_forward_address' => $fromAddr,
                'mail_forward_targets' => $toAddr,
            ];
            $resp = $this->kas->callForLogin($kasLogin, 'add_mail_forward', $payload);
            return (is_array($resp) ? $resp : ['success' => false, 'error' => 'Invalid KAS response']);
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'Handler exception: '.$e->getMessage()];
        }
    }
}
