<?php
/**
 * R3D KAS Manager – Recipe Action: AddMailaccount
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.10-alpha
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

    public function supports(string $type): bool { $t=strtolower(preg_replace("/[^a-z0-9]/","",$type)); return in_array($t,["addmailaccount","addmailacct"]); }

    public function handle(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        $kasLogin = $vars['kas_login'] ?? $run->kas_login ?? null;
        $mailLogin  = $vars['mail_login']  ?? $vars['mail_account'] ?? null;
        $mailDomain = $vars['mail_domain'] ?? $vars['domain'] ?? $vars['domain_name'] ?? null;
        $password   = $vars['mail_password'] ?? null;
        $quotaMb    = $vars['mail_quota_mb'] ?? $vars['mail_quota'] ?? null;

        // --- normalize aliases (legacy recipe vars) ---
        if (isset($vars['mail_adresses']) && !isset($vars['mail_addresses'])) {
            $vars['mail_addresses'] = $vars['mail_adresses'];
        }
        if (isset($vars['mail_quota_rule']) && !isset($vars['mail_quota'])) {
            $vars['mail_quota'] = $vars['mail_quota_rule'];
        }
        if (!isset($vars['mail_domain']) && isset($vars['domain_name'])) {
            $vars['mail_domain'] = $vars['domain_name'];
        }
        if (!isset($vars['mail_login']) && isset($vars['mail_account'])) {
            $vars['mail_login'] = $vars['mail_account'];
        }

        if (!$kasLogin || !$mailLogin || !$mailDomain || !$password) {
            return ['success'=>false,'error'=>'missing_parameters'];
        }

        if ($dryRun) {
            return ['success'=>true,'dry_run'=>true,'action'=>'add_mailaccount','params'=>compact('mailLogin','mailDomain','quotaMb')];
        }

        $kas = app(KasGateway::class);
        $resp = $kas->callForLogin($kasLogin, 'add_mailaccount', [
            // KAS API expects local_part/domain_part for add_mailaccount.
            'local_part' => $mailLogin,
            'domain_part' => $mailDomain,
            'mail_password' => $password,
            'webmail_autologin' => 'Y',
            // Note: mailbox quota is not settable via the documented API.
        ]);

        return ($resp['success'] ?? false)
            ? ['success'=>true,'action'=>'add_mailaccount','response'=>$resp]
            : ['success'=>false,'error'=>$resp['error'] ?? 'kas_failed','response'=>$resp];
    }
}
