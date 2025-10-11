<?php
/**
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.25.0-alpha
 * @date      2025-10-11
 *
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;
use App\Models\RecipeAction;
use App\Models\RecipeActionHistory;
use App\Models\KasClient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Exception;
use SoapClient;
use SoapFault;

class RecipeExecutor
{
    protected array $kasCredentials = [];

    /**
     * Main entry point to execute a recipe.
     */
    public function run(Recipe $recipe, array $variables = [], bool $dryRun = false): RecipeRun
    {
        return $this->executeRecipe($recipe, $variables, null, [
            'dryrun' => $dryRun,
        ]);
    }

    /**
     * Execute a full recipe with all actions.
     */
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
                $result = $this->dispatchAction($action, $run, $variables, $options);
            }

            $run->status = 'finished';
            $run->result = ['finished' => true];
        } catch (Exception $e) {
            $run->status = 'error';
            $run->result = ['error' => $e->getMessage()];
        }

        $run->finished_at = now();
        $run->save();

        return $run;
    }

    /**
     * Dispatches a single recipe action.
     */
    protected function dispatchAction(RecipeAction $action, RecipeRun $run, array $variables = [], array $options = [])
    {
        $params = array_merge($variables, $action->parameters ?? []);

        $methodName = match ($action->type) {
            'add_domain'           => 'actionAddDomain',
            'update_dns_records'   => 'actionUpdateDnsRecords',
            'add_mailaccount'      => 'actionAddMailaccount',
            'add_mail_forward'     => 'actionAddMailForward',
            default                => null,
        };

        if (!$methodName || !method_exists($this, $methodName)) {
            return ['error' => "Unknown action type: {$action->type}"];
        }

        if (!empty($options['dryrun'])) {
            return ['dryrun' => true, 'action' => $action->type];
        }

        $result = $this->$methodName($params, $run);
        $resultArray = (array) $result;

        $this->storeHistory($run, $action, $resultArray);

        return $resultArray;
    }

    /**
     * Store action result in history.
     */
    protected function storeHistory(RecipeRun $run, RecipeAction $action, array $result): void
    {
        RecipeActionHistory::create([
            'recipe_id'          => $run->recipe_id,
            'recipe_run_id'      => $run->id,
            'recipe_action_id'   => $action->id,
            'kas_login'          => $run->kas_login,
            'domain_name'        => $run->domain_name,
            'action_type'        => $action->type,
            'response_payload'   => $result,
            'status'             => data_get($result, 'Response.ReturnString', 'FALSE') === 'TRUE' ? 'success' : 'error',
            'created_at'         => now(),
        ]);
    }

    /**
     * Unified KAS SOAP call.
     */
    protected function kasApiCall(string $kasLogin, string $kasPassword, string $kasAction, array $kasParams = []): array
    {
        try {
            $kasPassword = Crypt::decryptString($kasPassword);
        } catch (Exception $e) {
        }

        $request = [
            'kas_login'        => $kasLogin,
            'kas_auth_type'    => 'plain',
            'kas_auth_data'    => $kasPassword,
            'kas_action'       => $kasAction,
            'KasRequestParams' => $kasParams,
        ];

        $soap = new SoapClient('https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl', ['exceptions' => true]);

        try {
            $raw = $soap->KasApi($request);

            if (is_array($raw)) {
                return $raw;
            }

            if (is_object($raw)) {
                return json_decode(json_encode($raw), true);
            }

            if (is_string($raw)) {
                return json_decode($raw, true) ?? ['Response' => ['ReturnString' => 'FALSE', 'Raw' => $raw]];
            }

            return ['Response' => ['ReturnString' => 'FALSE', 'RawType' => gettype($raw)]];
        } catch (SoapFault $e) {
            return ['Response' => ['ReturnString' => 'FALSE', 'error' => $e->getMessage()]];
        }
    }

    /**
     * Helper: resolve login → password.
     */
    protected function resolveKasLogin($run, $params)
    {
        return $params['kas_login'] ?? $run->kas_login ?? null;
    }

    /**
     * Add a domain.
     */
    protected function actionAddDomain(array $params, $run)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) {
            throw new Exception('Missing domain_name');
        }

        $kasLogin = $this->resolveKasLogin($run, $params);
        $client = KasClient::where('account_login', $kasLogin)->first();
        if (!$client) {
            throw new Exception("Unknown KAS account: {$kasLogin}");
        }

        $kasPassword = $client->account_password;

        $payload = [
            'domain'               => $domain,
            'php_version'          => $params['php_version'] ?? null,
            'domain_path'          => $params['domain_path'] ?? null,
            'domain_redirect_status' => $params['domain_redirect_status'] ?? null,
        ];

        return $this->kasApiCall($kasLogin, $kasPassword, 'add_domain', $payload);
    }

    /**
     * Update DNS records (custom policy).
     */
    protected function actionUpdateDnsRecords(array $params, $run)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) {
            throw new Exception('Missing domain_name for DNS update.');
        }

        $kasLogin = $this->resolveKasLogin($run, $params);
        $client = KasClient::where('account_login', $kasLogin)->first();
        if (!$client) {
            throw new Exception("Unknown KAS account: {$kasLogin}");
        }

        $kasPassword = $client->account_password;

        $records = [
            ['record_type' => 'A', 'record_name' => '', 'record_data' => '178.63.15.195'],
            ['record_type' => 'TXT', 'record_name' => '', 'record_data' => 'v=spf1 mx a ip4:178.63.15.195 -all'],
            ['record_type' => 'TXT', 'record_name' => '_dmarc', 'record_data' => 'v=DMARC1; p=quarantine; sp=quarantine; adkim=s; aspf=s'],
        ];

        $responses = [];
        foreach ($records as $rec) {
            $responses[] = $this->kasApiCall($kasLogin, $kasPassword, 'add_dns_settings', [
                'zone_host'    => $domain,
                'record_type'  => $rec['record_type'],
                'record_name'  => $rec['record_name'],
                'record_data'  => $rec['record_data'],
            ]);
        }

        return ['Response' => ['ReturnString' => 'TRUE', 'ReturnInfo' => $responses]];
    }

    /**
     * Add a mailbox (KAS: add_mailaccount).
     */
    protected function actionAddMailaccount(array $params, $run)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) {
            throw new Exception('Missing domain_name for mailbox creation.');
        }

        $kasLogin = $this->resolveKasLogin($run, $params);
        $client = KasClient::where('account_login', $kasLogin)->first();
        if (!$client) {
            throw new Exception("Unknown KAS account: {$kasLogin}");
        }

        $kasPassword = $client->account_password;

        $payload = [
            'mail_login'      => $params['mail_account'] ?? 'info',
            'mail_domain'     => $domain,
            'mail_password'   => $params['mail_password'] ?? $params['default_password'] ?? 'ChangeMe123!',
            'mail_quota_rule' => (string)($params['mail_quota_mb'] ?? 1024),
            'mail_spamcheck'  => 'yes',
            'mail_spamfilter' => 'yes',
            'mail_viruscheck' => 'yes',
            'mail_autoreply'  => 'no',
            'mail_catchall'   => 'no',
        ];

        return $this->kasApiCall($kasLogin, $kasPassword, 'add_mailaccount', $payload);
    }

    /**
     * Add mail forwarder (KAS: add_mail_forward).
     */
    protected function actionAddMailForward(array $params, $run)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) {
            throw new Exception('Missing domain_name for mail forwarder.');
        }

        $kasLogin = $this->resolveKasLogin($run, $params);
        $client = KasClient::where('account_login', $kasLogin)->first();
        if (!$client) {
            throw new Exception("Unknown KAS account: {$kasLogin}");
        }

        $kasPassword = $client->account_password;

        $payload = [
            'mail_source' => ($params['mail_forward_from'] ?? 'info') . '@' . $domain,
            'mail_target' => ($params['mail_forward_to'] ?? 'kontakt') . '@' . $domain,
        ];

        return $this->kasApiCall($kasLogin, $kasPassword, 'add_mail_forward', $payload);
    }
}
