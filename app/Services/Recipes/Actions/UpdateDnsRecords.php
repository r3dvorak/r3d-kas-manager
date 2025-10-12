<?php
/**
 * R3D KAS Manager – Recipe Action: AddMailforward
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.0-alpha
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/Recipes/Actions/AddMailforward.php
 *
 * Purpose:
 *  Create a mail forwarder on KAS (All-Inkl) using action type "add_mailforward".
 *
 * Expected inputs (vars + action parameters; action wins on conflicts):
 *  - kas_login        (string)  required; falls back to $run->kas_login
 *  - domain_name      (string)  required; falls back to $run->domain_name
 *  - mail_forward_from (string) local-part, e.g. "kontakt" (or full address)
 *  - mail_forward_to   (string|array) target address or comma-separated list or array
 *
 * Behavior:
 *  - Accepts 'mail_forward_to' as "foo@a.de,bar@b.de" or ['foo@a.de','bar@b.de'].
 *  - Sends payload using KAS action "add_mailforward" with keys:
 *      local_part, domain_part, target_1..target_N
 *  - On success does a best-effort sync via KasGateway::syncMailForward (if available).
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
     * Execute the add_mailforward action.
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
            return ['success' => true, 'dry_run' => true, 'action' => 'add_mailforward'];
        }

        // merge action parameters over recipe vars
        $p = array_merge($vars, is_array($action->parameters) ? $action->parameters : []);

        $kasLogin = $p['kas_login']   ?? $run->kas_login   ?? null;
        $domain   = $p['domain_name'] ?? $run->domain_name ?? null;

        if (!$kasLogin || !$domain) {
            return ['success' => false, 'error' => 'kas_login or domain_name missing'];
        }

        // Accept either full address or local-part for source
        $source = $p['mail_forward_from'] ?? $p['from_local'] ?? null;
        if (!$source) {
            return ['success' => false, 'error' => 'mail_forward_from missing'];
        }

        // If source is a full email, split; otherwise treat as local_part
        if (str_contains($source, '@')) {
            [$srcLocal, $srcDomain] = explode('@', $source, 2) + [null, null];
            $localPart = $srcLocal;
            // If domain was provided by full email, prefer it (but ensure matches domain_name)
            if ($srcDomain && strcasecmp($srcDomain, $domain) !== 0) {
                // Source address domain doesn't match recipe domain — signal error
                return ['success' => false, 'error' => "mail_forward_from domain ({$srcDomain}) does not match domain_name ({$domain})"];
            }
        } else {
            $localPart = $source;
        }

        // Targets: can be array or comma-separated string or single local-part (then append domain)
        $targetsRaw = $p['mail_forward_to'] ?? $p['to_list'] ?? $p['targets'] ?? null;
        $targets = [];

        if (is_array($targetsRaw)) {
            $targets = $targetsRaw;
        } elseif (is_string($targetsRaw) && $targetsRaw !== '') {
            // split by comma and trim
            $parts = array_filter(array_map('trim', explode(',', $targetsRaw)));
            $targets = array_values($parts);
        } elseif (is_string($targetsRaw) && $targetsRaw === null) {
            // no target provided
        }

        // If user provided a single local part (e.g., 'info'), convert to full address on same domain
        $normalizedTargets = [];
        foreach ($targets as $t) {
            if ($t === '') continue;
            if (!str_contains($t, '@')) {
                $normalizedTargets[] = $t . '@' . $domain;
            } else {
                $normalizedTargets[] = $t;
            }
        }

        if (empty($normalizedTargets)) {
            return ['success' => false, 'error' => 'No target address(es) provided for forward'];
        }

        // Build payload: local_part, domain_part, target_1..N
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
            // Use KasGateway to call add_mailforward (note action name: add_mailforward)
            $resp = $this->kas->call($kasLogin, $this->resolvePassword($kasLogin), 'add_mailforward', $payload);
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        // Best-effort: sync the forward locally if gateway provides a sync helper
        if (($resp['success'] ?? false) === true) {
            try {
                if (method_exists($this->kas, 'syncMailForward')) {
                    $this->kas->syncMailForward($kasLogin, $localPart, $domain);
                }
            } catch (Throwable) {
                // ignore sync problems
            }
        }

        return $resp;
    }

    /**
     * Helper to obtain decrypted password for a given kas login.
     * The KasGateway::callForLogin variant expects the Gateway to resolve the model.
     * Here we try to be explicit: if KasGateway provides callForLogin we could have used it,
     * but we already called call() directly in order to allow passing a password string.
     *
     * If your KasGateway exposes callForLogin, you can replace the call above with:
     *   $resp = $this->kas->callForLogin($kasLogin, 'add_mailforward', $payload);
     */
    private function resolvePassword(string $kasLogin): string
    {
        // Attempt to resolve via model; keep this method small so it's easy to change later.
        $client = \App\Models\KasClient::where('account_login', $kasLogin)->first();
        if ($client) {
            // Ensure your KasClient model exposes a decrypted account_password attribute.
            return $client->account_password;
        }
        // Fallback: empty password (will likely fail if KAS requires it)
        return '';
    }
}
