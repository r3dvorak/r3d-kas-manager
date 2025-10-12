<?php
/**
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.25.3-alpha
 * @date      2025-10-11
 * @license   MIT License
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;
use App\Models\RecipeAction;
use App\Models\RecipeActionHistory;
use App\Models\KasClient;
use Exception;
use SoapClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecipeExecutor
{
    /**
     * Execute a recipe and all actions.
     */
    public function executeRecipe(Recipe $recipe, array $variables = [], $user = null, array $options = []): RecipeRun
    {
        $dryRun = $options['dryrun'] ?? false;

        // merge recipe JSON variables with provided
        $stored = is_array($recipe->variables)
            ? $recipe->variables
            : (json_decode($recipe->variables, true) ?? []);
        $variables = array_merge($stored, $variables);

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
                $resp = $this->dispatchAction($action, $run, $variables, $dryRun);
                $this->logHistory($run, $action, $resp);
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
     * Dispatch a recipe action.
     */
    protected function dispatchAction(RecipeAction $action, RecipeRun $run, array $vars, bool $dryRun = false): array
    {
        $method = match ($action->type) {
            'add_domain'         => 'actionAddDomain',
            'update_dns_records' => 'actionUpdateDnsRecords',
            'add_mailaccount'    => 'actionAddMailaccount',
            'add_mail_forward'   => 'actionAddMailForward',
            default              => null,
        };

        if (!$method || !method_exists($this, $method)) {
            return ['success' => false, 'error' => "Unknown action type: {$action->type}"];
        }

        if ($dryRun) {
            return ['success' => true, 'dry_run' => true, 'action' => $action->type];
        }

        try {
            return $this->$method($vars, $run) ?? ['success' => false, 'error' => 'null response'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log action results.
     */
    protected function logHistory(RecipeRun $run, RecipeAction $action, ?array $resp = null): void
    {
        $resp = $resp ?? ['success' => false, 'error' => 'null response'];

        RecipeActionHistory::create([
            'recipe_id'        => $run->recipe_id,
            'recipe_run_id'    => $run->id,
            'recipe_action_id' => $action->id,
            'kas_login'        => $run->kas_login,
            'domain_name'      => $run->domain_name,
            'action_type'      => $action->type,
            'response_payload' => $resp,
            'status'           => ($resp['success'] ?? false) ? 'success' : 'error',
            'error_message'    => $resp['error'] ?? null,
            'created_at'       => now(),
        ]);
    }

    /**
     * Normalize KAS SOAP responses.
     */
    protected function normalizeResponse($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_object($raw)) {
            return json_decode(json_encode($raw), true) ?? [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            return ['Response' => ['ReturnString' => 'FALSE', 'ReturnInfo' => [], 'Raw' => $raw]];
        }

        return ['Response' => ['ReturnString' => 'FALSE', 'ReturnInfo' => []]];
    }

    /**
     * Safe KAS API call (bypasses SoapClient decoding issues).
     */
    protected function kasApiCall(string $kasLogin, string $password, string $actionName, array $params = []): array
    {
        if (empty($kasLogin) || empty($password)) {
            throw new \Exception('Missing KAS credentials for SOAP call');
        }

        $wsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
        $soap = new \SoapClient($wsdl, [
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace'      => true,
            'features'   => SOAP_SINGLE_ELEMENT_ARRAYS,
        ]);

        // KAS expects a SINGLE JSON STRING argument to KasApi
        $payload = [
            'kas_login'        => $kasLogin,
            'kas_auth_type'    => 'plain',
            'kas_auth_data'    => $password,
            'kas_action'       => $actionName,
            'KasRequestParams' => $params,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            // IMPORTANT: pass THE JSON STRING, not an array
            $raw = $soap->__soapCall('KasApi', [$json]);

            // ---- robust normalization (no double-decode) ----
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : ['Response' => ['Raw' => $raw]];
            } elseif (is_object($raw)) {
                $raw = json_decode(json_encode($raw), true) ?? [];
            } elseif (!is_array($raw)) {
                $raw = ['Response' => ['Raw' => $raw]];
            }
            // -------------------------------------------------

            // Unify shape
            $response = $raw['Response'] ?? $raw;
            $return   = $response['ReturnString'] ?? ($raw['ReturnString'] ?? null);
            $success  = (string)$return === 'TRUE';

            return [
                'success'  => $success,
                'Response' => $response,
            ];
        } catch (\SoapFault $e) {
            $msg = $e->faultstring ?? $e->getMessage();

            // KAS flood protection edge case
            if (stripos($msg, 'flood_protection') !== false) {
                sleep(2);
                $raw = $soap->__soapCall('KasApi', [$json]);
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $raw = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : ['Response' => ['Raw' => $raw]];
                } elseif (is_object($raw)) {
                    $raw = json_decode(json_encode($raw), true) ?? [];
                } elseif (!is_array($raw)) {
                    $raw = ['Response' => ['Raw' => $raw]];
                }
                $response = $raw['Response'] ?? $raw;
                $return   = $response['ReturnString'] ?? ($raw['ReturnString'] ?? null);
                $success  = (string)$return === 'TRUE';

                return [
                    'success'  => $success,
                    'Response' => $response,
                ];
            }

            // surface server message (what you were seeing)
            throw new \Exception("KAS SOAP error: {$msg}");
        } catch (\Throwable $e) {
            throw new \Exception("KAS SOAP error: " . $e->getMessage());
        }
    }

    /**
     * Resolve the active KasClient.
     */
    protected function kasClient(array $params, RecipeRun $run): KasClient
    {
        $login = $params['kas_login'] ?? $run->kas_login ?? null;
        if (!$login) {
            throw new Exception('kas_login missing');
        }

        $client = KasClient::where('account_login', $login)->first();
        if (!$client) {
            throw new Exception("Unknown KAS account: {$login}");
        }

        return $client;
    }

    // ------------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------------

    protected function actionAddDomain(array $p, RecipeRun $run): array
    {
        $client = $this->kasClient($p, $run);
        $domain = $p['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) throw new Exception('domain_name missing');

        $payload = [
            'domain'      => $domain,
            'php_version' => $p['php_version'] ?? '8.3',
        ];

        return $this->kasApiCall(
            $client->account_login,
            $client->account_password,
            'add_domain',
            $payload
        );
    }

    protected function actionUpdateDnsRecords(array $p, RecipeRun $run): array
    {
        $client = $this->kasClient($p, $run);
        $domain = $p['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) throw new Exception('domain_name missing');

        $records = [
            ['record_type' => 'A',   'record_name' => '',       'record_data' => '178.63.15.195'],
            ['record_type' => 'TXT', 'record_name' => '',       'record_data' => 'v=spf1 mx a ip4:178.63.15.195 -all'],
            ['record_type' => 'TXT', 'record_name' => '_dmarc', 'record_data' => 'v=DMARC1; p=quarantine; sp=quarantine; adkim=s; aspf=s'],
        ];

        $responses = [];
        foreach ($records as $rec) {
            $responses[] = $this->kasApiCall(
                $client->account_login,
                $client->account_password,
                'add_dns_settings',
                [
                    'zone_host'   => $domain,
                    'record_type' => $rec['record_type'],
                    'record_name' => $rec['record_name'],
                    'record_data' => $rec['record_data'],
                ]
            );
        }

        return [
            'success'  => true,
            'Response' => ['ReturnString' => 'TRUE', 'ReturnInfo' => array_values($responses)],
        ];
    }

    protected function actionAddMailaccount(array $p, RecipeRun $run): array
    {
        $client = $this->kasClient($p, $run);
        $domain = $p['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) throw new \Exception('domain_name missing');

        $payload = [
            'local_part'    => $p['mail_account'] ?? 'info',
            'domain_part'   => $domain,
            'mail_password' => $p['mail_password'] ?? 'ChangeMe123!',
        ];

        $resp = $this->kasApiCall(
            $client->account_login,
            $client->account_password,
            'add_mailaccount',
            $payload
        );

        if (($resp['success'] ?? false) === true) {
            // KAS can need ~1s until GET endpoints reflect new state
            usleep(600000); // 0.6s
            $this->syncMailAccountFor($client, $payload['local_part'], $domain);
        }

        return $resp;
    }

    protected function actionAddMailForward(array $p, RecipeRun $run): array
    {
        $client = $this->kasClient($p, $run);
        $domain = $p['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) throw new \Exception('domain_name missing');

        $sourceLocal = $p['mail_forward_from'] ?? 'kontakt';
        $targetLocal = $p['mail_forward_to']   ?? 'info';

        $payload = [
            'local_part'  => $sourceLocal,
            'domain_part' => $domain,
            'target_1'    => $targetLocal . '@' . $domain,
        ];

        $resp = $this->kasApiCall(
            $client->account_login,
            $client->account_password,
            'add_mailforward',
            $payload
        );

        if (($resp['success'] ?? false) === true) {
            usleep(600000);
            $this->syncMailForwardFor($client, $sourceLocal, $domain);
        }

        return $resp;
    }

    /**
     * Sync one mailbox (local_part@domain) from KAS into kas_mailaccounts.
     * Uses your columns: kas_login, mail_login, domain, email, status, data_json, client_id.
     */
    public function syncMailAccountFor(\App\Models\KasClient $client, string $localPart, string $domain): void
    {
        $kasLogin = $client->account_login;

        $res  = $this->kasApiCall($kasLogin, $client->account_password, 'get_mailaccounts', []);
        $rows = $this->extractArray($res);

        foreach ($rows as $row) {
            // KAS gives full email in mail_adresses/mail_addresses
            $email = $row['mail_adresses'] ?? $row['mail_addresses'] ?? null;
            if (!$email) continue;

            // target only our mailbox
            if (strcasecmp($email, $localPart . '@' . $domain) !== 0) continue;

            $login  = $row['mail_login'] ?? null;
            $status = (isset($row['mail_is_active']) && $row['mail_is_active'] === 'Y') ? 'active' : 'missing';
            $dom    = substr(strrchr($email, '@'), 1) ?: $domain;

            $payload = [
                'kas_login'  => $kasLogin,
                'mail_login' => $login ?: $email,   // required by your schema
                'domain'     => $dom,
                'email'      => $email,
                'status'     => $status,
                'client_id'  => $client->id,
                'data_json'  => $row,              // JSON column
            ];

            // unique on (kas_login, mail_login) fits your schema well
            $this->upsertLocal(
                'kas_mailaccounts',
                ['kas_login' => $kasLogin, 'mail_login' => $payload['mail_login']],
                $payload
            );
            break;
        }
    }


    /**
     * Sync one forward (local_part@domain) from KAS into kas_mailforwards.
     * Uses your columns: kas_login, mail_forward_address, mail_forward_targets, status, client_id, data_json.
     */
    public function syncMailForwardFor(\App\Models\KasClient $client, string $sourceLocal, string $domain): void
    {
        $kasLogin = $client->account_login;

        $res  = $this->kasApiCall($kasLogin, $client->account_password, 'get_mailforwards', []);
        $rows = $this->extractArray($res);

        foreach ($rows as $row) {
            $addr    = $row['mail_forward_address'] ?? $row['mail_forward_adress'] ?? null; // both spellings appear
            if (!$addr) continue;

            if (strcasecmp($addr, $sourceLocal . '@' . $domain) !== 0) continue;

            $targets = $row['mail_forward_targets'] ?? null; // already comma-joined by KAS here
            $status  = (isset($row['in_progress']) && strtoupper($row['in_progress']) === 'FALSE') ? 'active' : 'missing';

            $payload = [
                'kas_login'            => $kasLogin,
                'mail_forward_address' => $addr,
                'mail_forward_targets' => $targets,
                'status'               => $status,
                'client_id'            => $client->id,
                'data_json'            => $row,
            ];

            // unique on (kas_login, mail_forward_address)
            $this->upsertLocal(
                'kas_mailforwards',
                ['kas_login' => $kasLogin, 'mail_forward_address' => $addr],
                $payload
            );
            break;
        }
    }

    /**
     * Upsert safely: only persist columns that exist in the table.
     */
    protected function upsertLocal(string $table, array $unique, array $data): void
    {
        $cols = Schema::getColumnListing($table);
        $colset = array_flip($cols);

        // JSON-encode any '*_json' field automatically
        foreach ($data as $k => $v) {
            if (str_ends_with($k, '_json') && (is_array($v) || is_object($v))) {
                $data[$k] = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
        }

        $filteredData   = array_intersect_key($data,   $colset);
        $filteredUnique = array_intersect_key($unique, $colset);

        // sensible fallback unique keys
        if (empty($filteredUnique)) {
            if (isset($filteredData['email'])) {
                $filteredUnique = ['email' => $filteredData['email']];
            } elseif (isset($filteredData['mail_login'])) {
                $filteredUnique = ['mail_login' => $filteredData['mail_login']];
            } elseif (isset($filteredData['mail_forward_address'])) {
                $filteredUnique = ['mail_forward_address' => $filteredData['mail_forward_address']];
            } else {
                DB::table($table)->insert($filteredData);
                return;
            }
        }

        DB::table($table)->updateOrInsert($filteredUnique, $filteredData);
    }

    /**
     * Extracts the row list from a KAS response.
     */
    protected function extractArray(array $resp): array
    {
        $r = $resp['Response']['ReturnInfo'] ?? $resp['Response'] ?? $resp;
        if (!is_array($r)) return [];
        return array_is_list($r) ? $r : array_values($r);
    }

    /** Public helper: fetch all mailaccounts from KAS (normalized list) */
    public function fetchMailaccounts(\App\Models\KasClient $client): array
    {
        $resp = $this->kasApiCall($client->account_login, $client->account_password, 'get_mailaccounts', []);
        return $this->extractArray($resp); // uses the helper we added earlier
    }

    /** Public helper: fetch all mailforwards from KAS (normalized list) */
    public function fetchMailforwards(\App\Models\KasClient $client): array
    {
        $resp = $this->kasApiCall($client->account_login, $client->account_password, 'get_mailforwards', []);
        return $this->extractArray($resp);
    }

}
