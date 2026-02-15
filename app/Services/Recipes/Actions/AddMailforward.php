<?php
/**
 * R3D KAS Manager – Recipe Action: AddMailforward
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.10-alpha
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
    public function supports(string $type): bool { 
        $t=strtolower(preg_replace("/[^a-z0-9]/","",$type)); 
        return in_array($t,["addmailforward","addmailfwd"]); 
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
        $kasLogin  = $vars['kas_login'] ?? $run->kas_login ?? null;
        $mailDomain= $vars['mail_domain'] ?? $vars['domain'] ?? $vars['domain_name'] ?? null;
        $address   = $vars['mail_forward_address'] ?? $vars['forward_address'] ?? null;
        $targets   = $vars['mail_forward_targets'] ?? $vars['forward_targets'] ?? null;

        if (!$kasLogin || !$mailDomain || !$address || !$targets) {
            return ['success'=>false,'error'=>'missing_parameters'];
        }

        if ($dryRun) {
            return ['success'=>true,'dry_run'=>true,'action'=>'add_mail_forward','params'=>compact('address','targets','mailDomain')];
        }

        $kas = app(KasGateway::class);
        [$local, $domain] = str_contains($address, '@')
            ? explode('@', strtolower($address), 2)
            : [strtolower($address), strtolower($mailDomain)];

        $targetsList = is_array($targets)
            ? $targets
            : preg_split('/[\\s,;]+/', (string) $targets) ?: [];

        $params = [
            'local_part' => $local,
            'domain_part' => $domain,
        ];

        $i = 0;
        foreach ($targetsList as $t) {
            $t = strtolower(trim((string) $t));
            if ($t === '') continue;
            if (!str_contains($t, '@')) {
                $t = $t . '@' . $domain;
            }
            $i++;
            $params['target_' . $i] = $t;
        }

        $resp = $kas->callForLogin($kasLogin, 'add_mailforward', $params);

        return ($resp['success'] ?? false)
            ? ['success'=>true,'action'=>'add_mail_forward','response'=>$resp]
            : ['success'=>false,'error'=>$resp['error'] ?? 'kas_failed','response'=>$resp];
    }
}
