<?php
/**
 * R3D KAS Manager – Recipe Executor (orchestrates recipe runs)
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.26.1
 * @date      2025-10-12
 * @license   MIT License
 *
 * app/Services/RecipeExecutor.php
 *
 * Note:
 *  This file contains conservative, defensive JSON normalization to avoid
 *  "json_decode(): Argument #1 must be string, array given" diagnostics.
 *  It also provides a backwards-compatible kasApiCall(...) variant that accepts
 *  login/password strings (used by older code), while your new handlers use KasGateway.
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;
use App\Models\RecipeAction;
use App\Models\RecipeActionHistory;
use App\Models\KasClient;
use Illuminate\Support\Facades\Log;
use Exception;
use SoapClient;

class RecipeExecutor
{
    /**
     * Execute a recipe (backwards-compatible run/execute entrypoints)
     */
    public function run(Recipe $recipe, array $variables = [], bool $dryRun = false): RecipeRun
    {
        return $this->executeRecipe($recipe, $variables, null, ['dryrun' => $dryRun]);
    }

    public function executeRecipe(Recipe $recipe, array $variables = [], $user = null, array $options = []): RecipeRun
    {
        $run = RecipeRun::create([
            'recipe_id'   => $recipe->id,
            'user_id'     => $user?->id ?? null,
            'status'      => 'running',
            'kas_login'   => $variables['kas_login'] ?? null,
            'domain_name' => $variables['domain_name'] ?? null,
            'variables'   => $variables,
            'started_at'  => now(),
        ]);

        try {
            foreach ($recipe->actions as $action) {
                // dispatch to handler method if present, fallback to generic dispatchAction()
                $result = $this->dispatchAction($action, $run, $variables, $options);
            }

            $run->status = 'finished';
            $run->result = ['finished' => true];
        } catch (Exception $e) {
            $run->status = 'error';
            $run->result = ['error' => $e->getMessage()];
            Log::error('RecipeExecutor error: ' . $e->getMessage());
        }

        $run->finished_at = now();
        $run->save();

        return $run;
    }

    /**
     * Minimal dispatcher to call in-file handlers named action<PascalCase>()
     * or fall back to a simple "not implemented" result.
     */
    protected function dispatchAction(RecipeAction $action, RecipeRun $run, array $variables = [], array $options = []): array
    {
        $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('_', ' ', $action->type)));

        // Dry-run quick return
        if (!empty($options['dryrun'])) {
            // store a dry_run history entry
            $this->storeHistory($run, $action, ['success' => false, 'error' => null, 'dry_run' => true]);
            return ['dryrun' => true];
        }

        if (method_exists($this, $methodName)) {
            $params = array_merge($variables, is_array($action->parameters) ? $action->parameters : []);
            $resp = $this->{$methodName}($params, $run);
        } else {
            $resp = ['success' => false, 'error' => "Unknown action type: {$action->type}"];
        }

        // Ensure normalized array
        $respArr = is_array($resp) ? $resp : ['success' => false, 'error' => 'Invalid handler response'];
        $this->storeHistory($run, $action, $respArr);

        return $respArr;
    }

    /**
     * Store action history record
     */
    protected function storeHistory(RecipeRun $run, RecipeAction $action, array $result): void
    {
        RecipeActionHistory::create([
            'recipe_id'         => $run->recipe_id,
            'recipe_run_id'     => $run->id,
            'recipe_action_id'  => $action->id,
            'kas_login'         => $run->kas_login,
            'domain_name'       => $run->domain_name,
            'action_type'       => $action->type,
            'request_payload'   => $action->parameters ?? null,
            'response_payload'  => $result,
            'status'            => ($result['success'] ?? false) ? 'success' : 'error',
            'error_message'     => $result['error'] ?? null,
            'created_at'        => now(),
        ]);
    }

    /**
     * Backwards-compatible KAS API call helper that accepts $kasLogin, $password strings.
     *
     * Safe normalization prevents json_decode() being called on arrays.
     */
    protected function kasApiCall(string $kasLogin, string $password, string $actionName, array $params = []): array
    {
        $wsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
        $soap = new SoapClient($wsdl, [
            'trace'      => 1,
            'exceptions' => true,
            'features'   => SOAP_SINGLE_ELEMENT_ARRAYS,
        ]);

        $payload = [
            'kas_login'        => $kasLogin,
            'kas_auth_type'    => 'plain',
            'kas_auth_data'    => $password,
            'kas_action'       => $actionName,
            'KasRequestParams' => $params,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $raw = $soap->__soapCall('KasApi', [$json]);

            // Defensive normalization:
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $raw = $decoded;
                } else {
                    $raw = ['Response' => ['Raw' => $raw]];
                }
            } elseif (is_object($raw)) {
                $raw = json_decode(json_encode($raw), true) ?? [];
            } elseif (!is_array($raw)) {
                $raw = ['Response' => ['Raw' => $raw]];
            }

            $resp = $raw['Response'] ?? $raw;
            $ok   = (string)($resp['ReturnString'] ?? $raw['ReturnString'] ?? 'TRUE') === 'TRUE';

            return ['success' => $ok, 'Response' => $resp, 'raw' => $raw];
        } catch (\SoapFault $e) {
            $msg = $e->faultstring ?? $e->getMessage();
            return ['success' => false, 'error' => "KAS SOAP error: {$msg}"];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => "KAS error: " . $e->getMessage()];
        }
    }

    /* -----------------------------------------------------------------
     * Legacy in-file action handlers (lightweight compatibility).
     * New split handlers live under App\Services\Recipes\Actions\*
     * ----------------------------------------------------------------- */

    protected function actionAddDomain(array $params, $run)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) {
            return ['success' => false, 'error' => 'Missing domain_name'];
        }
        $kasLogin = $params['kas_login'] ?? $run->kas_login ?? null;
        if (!$kasLogin) {
            return ['success' => false, 'error' => 'kas_login missing'];
        }

        // resolve password from model
        $client = KasClient::where('account_login', $kasLogin)->first();
        if (!$client) {
            return ['success' => false, 'error' => "Unknown KAS account: {$kasLogin}"];
        }

        return $this->kasApiCall($kasLogin, $client->account_password, 'add_domain', [
            'domain' => $domain,
            'php_version' => $params['php_version'] ?? null,
        ]);
    }

    protected function actionUpdateDnsRecords(array $params, $run)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) {
            return ['success' => false, 'error' => 'Missing domain_name for DNS update.'];
        }

        $kasLogin = $params['kas_login'] ?? $run->kas_login ?? null;
        if (!$kasLogin) {
            return ['success' => false, 'error' => 'kas_login missing'];
        }

        $client = KasClient::where('account_login', $kasLogin)->first();
        if (!$client) {
            return ['success' => false, 'error' => "Unknown KAS account: {$kasLogin}"];
        }

        $records = $params['records'] ?? [
            ['record_type'=>'A',   'record_name'=>'',       'record_data'=>'178.63.15.195'],
            ['record_type'=>'TXT', 'record_name'=>'',       'record_data'=>'v=spf1 mx a ip4:178.63.15.195 -all'],
            ['record_type'=>'TXT', 'record_name'=>'_dmarc', 'record_data'=>'v=DMARC1; p=quarantine; sp=quarantine; adkim=s; aspf=s'],
        ];

        $ok = true; $items = [];
        foreach ($records as $r) {
            $resp = $this->kasApiCall($kasLogin, $client->account_password, 'add_dns_settings', [
                'zone_host'   => $domain,
                'record_type' => $r['record_type'],
                'record_name' => $r['record_name'] ?? '',
                'record_data' => $r['record_data'] ?? '',
            ]);
            $ok = $ok && ($resp['success'] ?? false);
            $items[] = $resp;
        }

        return ['success' => $ok, 'Response' => ['items' => $items]];
    }

    protected function actionAddMailaccount(array $params, $run)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) {
            return ['success' => false, 'error' => 'Missing domain_name for mailbox creation.'];
        }

        $kasLogin = $this->resolveKasLogin($run, $params);
        if (!$kasLogin) {
            return ['success' => false, 'error' => 'kas_login missing'];
        }

        $client = KasClient::where('account_login', $kasLogin)->first();
        if (!$client) {
            return ['success' => false, 'error' => "Unknown KAS account: {$kasLogin}"];
        }

        $payload = [
            'local_part'    => $params['mail_account'] ?? 'info',
            'domain_part'   => $domain,
            'mail_password' => $params['mail_password'] ?? $params['default_password'] ?? 'ChangeMe123!',
        ];
        if (!empty($params['mail_quota_mb'])) {
            $payload['quota_rule'] = (int)$params['mail_quota_mb'];
        }

        return $this->kasApiCall($kasLogin, $client->account_password, 'add_mailaccount', $payload);
    }

    protected function actionAddMailForward(array $params, $run)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) {
            return ['success' => false, 'error' => 'Missing domain_name for mail forward.'];
        }

        $kasLogin = $this->resolveKasLogin($run, $params);
        if (!$kasLogin) {
            return ['success' => false, 'error' => 'kas_login missing'];
        }

        $client = KasClient::where('account_login', $kasLogin)->first();
        if (!$client) {
            return ['success' => false, 'error' => "Unknown KAS account: {$kasLogin}"];
        }

        // support both local-part or full address in mail_forward_from
        $source = $params['mail_forward_from'] ?? $params['from_local'] ?? null;
        if (!$source) {
            return ['success' => false, 'error' => 'mail_forward_from missing'];
        }
        if (str_contains($source, '@')) {
            [$local, $dom] = explode('@', $source, 2) + [null, null];
            if ($dom && strcasecmp($dom, $domain) !== 0) {
                return ['success' => false, 'error' => 'mail_forward_from domain mismatch'];
            }
            $localPart = $local;
        } else {
            $localPart = $source;
        }

        $targetsRaw = $params['mail_forward_to'] ?? $params['mail_forward_targets'] ?? null;
        $targets = [];
        if (is_array($targetsRaw)) $targets = $targetsRaw;
        elseif (is_string($targetsRaw) && $targetsRaw !== '') $targets = array_filter(array_map('trim', explode(',', $targetsRaw)));

        if (empty($targets)) {
            return ['success' => false, 'error' => 'No mail_forward_to provided'];
        }

        $payload = [
            'local_part'  => $localPart,
            'domain_part' => $domain,
        ];
        $i = 1;
        foreach ($targets as $t) {
            $payload['target_' . $i] = (str_contains($t, '@') ? $t : ($t . '@' . $domain));
            $i++;
        }

        return $this->kasApiCall($kasLogin, $client->account_password, 'add_mailforward', $payload);
    }

    protected function resolveKasLogin($run, $params)
    {
        return $params['kas_login'] ?? $run->kas_login ?? null;
    }
}
