<?php
/**
 * RecipeExecutor
 *
 * Executes Recipe actions in order, logs runs and action history,
 * provides basic KAS API wrapper with flood-protection retry.
 *
 * Usage:
 *   $executor = new \App\Services\RecipeExecutor();
 *   $run = $executor->executeRecipe($recipe, $variables, $user, $options);
 *
 * Notes:
 *  - Reads KAS credentials from storage/kas_responses/get_accounts.json
 *  - Handles request/response logging in recipe_action_history
 *  - stop_on_error default true (can be overridden per-action via parameters.continue_on_error)
 */

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRun;
use App\Models\RecipeAction;
use App\Models\RecipeActionHistory;
use App\Models\KasTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;
use Exception;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;

class RecipeExecutor
{
    use DispatchesJobs;

    protected string $kasWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';
    protected int $delay = 2; // seconds between retries in flood_protection

    /** @var array account_login => password */
    protected array $kasCredentials = [];

    public function __construct()
    {
        $this->loadKasCredentials();
    }

    /**
     * Load credentials from storage/kas_responses/get_accounts.json
     */
    protected function loadKasCredentials(): void
    {
        $path = base_path('storage/kas_responses/get_accounts.json');
        if (!file_exists($path)) {
            $this->kasCredentials = [];
            return;
        }

        $raw = json_decode(file_get_contents($path), true);
        $entries = $raw['Response']['ReturnInfo'] ?? $raw['response']['data'] ?? null;
        if (!is_array($entries)) {
            $this->kasCredentials = [];
            return;
        }

        $creds = [];
        foreach ($entries as $item) {
            // accommodate both account_login/account_password and account_login/password
            $login = $item['account_login'] ?? $item['login'] ?? null;
            $pw = $item['account_password'] ?? $item['password'] ?? null;
            if ($login && $pw) {
                $creds[$login] = $pw;
            }
        }
        $this->kasCredentials = $creds;
    }

    /**
     * Execute a Recipe.
     *
     * @param Recipe $recipe
     * @param array $variables - runtime variables (overrides recipe.variables)
     * @param \App\Models\User|null $user
     * @param array $options - optional keys: kas_login, domain_name, dryrun (bool)
     *
     * @return RecipeRun
     */
    public function executeRecipe(Recipe $recipe, array $variables = [], $user = null, array $options = []): RecipeRun
    {
        // create run record
        $run = RecipeRun::create([
            'recipe_id'   => $recipe->id,
            'user_id'     => $user?->id ?? null,
            'status'      => 'running',
            'kas_login'   => $options['kas_login'] ?? ($variables['kas_login'] ?? null),
            'domain_name' => $options['domain'] ?? ($variables['domain_name'] ?? null),
            'started_at'  => now(),
            'variables'   => array_merge($recipe->variables ?? [], $variables),
        ]);

        $actions = $recipe->actions()->orderBy('order')->get();
        $stopOnErrorGlobal = $options['stop_on_error'] ?? true;

        foreach ($actions as $action) {
            $history = $this->createHistoryRow($recipe, $run, $action, $user, $options);

            // prepare action params + replace placeholders
            $params = $this->resolveParameters($action->parameters ?? [], $run->variables ?? []);

            // dry-run handling
            if (!empty($options['dryrun'])) {
                $history->request_payload = $params;
                $history->response_payload = ['dryrun' => true];
                $history->status = 'success';
                $history->started_at = now();
                $history->finished_at = now();
                $history->save();
                continue;
            }

            // execute
            $history->started_at = now();
            $history->save();

            try {
                $result = $this->dispatchAction($action->type, $params, $run);
                $history->response_payload = $result;
                $history->status = 'success';
                $history->finished_at = now();
                $history->save();
            } catch (Exception $e) {
                $history->response_payload = null;
                $history->error_message = substr($e->getMessage(), 0, 1000);
                $history->status = 'failed';
                $history->finished_at = now();
                $history->save();

                // decide whether to continue
                $continueOnErr = $params['continue_on_error'] ?? false;
                if (!$continueOnErr && $stopOnErrorGlobal) {
                    $run->status = 'error';
                    $run->result = ['error' => $e->getMessage()];
                    $run->finished_at = now();
                    $run->save();
                    return $run; // stop execution
                }
                // else continue to next action
            }

            // small pause between actions to be gentle to KAS API
            sleep(1);
        }

        // finished all actions
        $run->status = 'finished';
        $run->result = ['finished' => true];
        $run->finished_at = now();
        $run->save();

        return $run;
    }

    /**
     * Create a blank history row
     */
    protected function createHistoryRow(Recipe $recipe, $run, RecipeAction $action, $user = null, array $options = []): RecipeActionHistory
    {
        return RecipeActionHistory::create([
            'recipe_id' => $recipe->id,
            'recipe_run_id' => $run->id,
            'recipe_action_id' => $action->id,
            'user_id' => $user?->id ?? null,
            'kas_client_id' => $options['kas_client_id'] ?? null,
            'kas_login' => $run->kas_login,
            'domain_name' => $run->domain_name,
            'action_type' => $action->type,
            'status' => 'pending',
            'started_at' => null,
            'finished_at' => null,
        ]);
    }

    /**
     * Resolve (merge) parameters and substitute simple placeholders like {{domain}} or {domain}
     */
    protected function resolveParameters($parameters, $variables)
    {
        $params = is_array($parameters) ? $parameters : [];
        $vars = is_array($variables) ? $variables : [];

        $body = json_encode($params);
        if ($body === false) return $params;

        // replace {{key}} and {key} placeholders
        foreach ($vars as $k => $v) {
            $body = str_replace(['{{'.$k.'}}','{'.$k.'}'], (string)$v, $body);
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : $params;
    }

    /**
     * Dispatch action by type
     */
    protected function dispatchAction(string $type, array $params, $run)
    {
        switch ($type) {
            case 'add_domain':
                return $this->actionAddDomain($params, $run);
            case 'apply_template':
                return $this->actionApplyTemplate($params, $run);
            case 'create_mailaccount':
                return $this->actionCreateMailaccount($params, $run);
            case 'create_forwarders':
            case 'create_forwarder':
                return $this->actionCreateForwarders($params, $run);
            case 'add_dns_record':
                return $this->actionAddDnsRecord($params, $run);
            case 'enable_ssl':
                return $this->actionEnableSsl($params, $run);
            default:
                throw new Exception("Unknown action type: {$type}");
        }
    }

    /* ---------- Action handlers ---------- */

    protected function actionAddDomain(array $params, $run)
    {
        // required: domain_name, optional php_version, target_path, redirect
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) throw new Exception('Missing domain_name');

        $kasLogin = $this->resolveKasLogin($run, $params);
        $pw = $this->kasCredentials[$kasLogin] ?? null;
        if (!$pw) throw new Exception("Missing credentials for {$kasLogin}");

        $payload = [
            'domain' => $domain,
            'php_version' => $params['php_version'] ?? null,
            'domain_path' => $params['domain_path'] ?? null,
            'domain_redirect_status' => $params['domain_redirect_status'] ?? null,
        ];

        return $this->kasApiCall($kasLogin, $pw, 'add_domain', $payload);
    }

    protected function actionApplyTemplate(array $params, $run)
    {
        // expects either template_id or (template_type + template_name)
        if (!empty($params['template_id'])) {
            $tmpl = KasTemplate::find($params['template_id']);
        } else {
            $tmpl = KasTemplate::where('template_type', $params['template_type'] ?? '')
                ->where('template_name', $params['template_name'] ?? '')
                ->first();
        }

        if (!$tmpl) {
            throw new Exception('Template not found');
        }

        $data = $tmpl->data_json;
        if (is_string($data)) $data = json_decode($data, true);
        if (!is_array($data)) throw new Exception('Invalid template data_json');

        // handle template types: dns, mail, php, ssl (we implement dns & mail)
        switch ($tmpl->template_type) {
            case 'dns':
                return $this->applyDnsTemplate($data, $run, $params);
            case 'mail':
                return $this->applyMailTemplate($data, $run, $params);
            case 'php':
            case 'ssl':
            default:
                // for other types return info for manual handling
                return ['applied' => false, 'reason' => 'template_type not automatically applied: '.$tmpl->template_type];
        }
    }

    protected function applyDnsTemplate(array $records, $run, array $params)
    {
        $domain = $params['domain_name'] ?? $run->domain_name ?? null;
        if (!$domain) throw new Exception('Missing domain for DNS template');

        $kasLogin = $this->resolveKasLogin($run, $params);
        $pw = $this->kasCredentials[$kasLogin] ?? null;
        if (!$pw) throw new Exception("Missing credentials for {$kasLogin}");

        $results = [];
        foreach ($records as $record) {
            // map record keys expected by add_dns_record
            $payload = [
                'zone_host'    => $domain,
                'record_name'  => $record['record_name'] ?? ($record['name'] ?? ''),
                'record_type'  => $record['record_type'] ?? 'A',
                'record_data'  => $record['record_data'] ?? '',
                'record_aux'   => $record['record_aux'] ?? ($record['aux'] ?? 0),
            ];
            $results[] = $this->kasApiCall($kasLogin, $pw, 'add_dns_record', $payload);
            // small delay
            sleep(1);
        }
        return $results;
    }

    protected function applyMailTemplate(array $data, $run, array $params)
    {
        $kasLogin = $this->resolveKasLogin($run, $params);
        $pw = $this->kasCredentials[$kasLogin] ?? null;
        if (!$pw) throw new Exception("Missing credentials for {$kasLogin}");

        $created = [];
        $mailboxes = $data['mailboxes'] ?? [];
        foreach ($mailboxes as $mb) {
            $addressLocal = $mb['local'] ?? null;
            if (!$addressLocal) continue;
            $address = ($addressLocal . '@' . ($params['domain_name'] ?? $run->domain_name ?? ''));
            $payload = [
                'mail_login' => $mb['mail_login'] ?? null,
                'mail_adresses' => $address,
                'mail_password' => $mb['password'] ?? ($mb['default_password'] ?? ($params['default_password'] ?? 'auto')),
                'quota_rule' => $mb['quota_mb'] ?? $mb['quota'] ?? null,
            ];
            $created[] = $this->kasApiCall($kasLogin, $pw, 'add_mailaccount', $payload);
            // small delay
            sleep(1);
        }
        return $created;
    }

    protected function actionCreateMailaccount(array $params, $run)
    {
        $kasLogin = $this->resolveKasLogin($run, $params);
        $pw = $this->kasCredentials[$kasLogin] ?? null;
        if (!$pw) throw new Exception("Missing credentials for {$kasLogin}");

        $payload = [
            'mail_login' => $params['mail_login'] ?? null,
            'mail_adresses' => $params['mail_adresses'] ?? $params['mail_address'] ?? null,
            'mail_password' => $params['mail_password'] ?? null,
            'quota_rule' => $params['quota_rule'] ?? null,
        ];

        return $this->kasApiCall($kasLogin, $pw, 'add_mailaccount', $payload);
    }

    protected function actionCreateForwarders(array $params, $run)
    {
        $kasLogin = $this->resolveKasLogin($run, $params);
        $pw = $this->kasCredentials[$kasLogin] ?? null;
        if (!$pw) throw new Exception("Missing credentials for {$kasLogin}");

        $items = $params['items'] ?? [];
        if (isset($params['source']) && isset($params['targets'])) {
            // single definition
            $items = [['source' => $params['source'], 'targets' => $params['targets'], 'spamfilter' => $params['spamfilter'] ?? 'pdw']];
        }

        $created = [];
        foreach ($items as $it) {
            $payload = [
                'mail_forward_adress' => $it['source'],
                'mail_forward_targets' => is_array($it['targets']) ? implode(',', $it['targets']) : $it['targets'],
                'mail_forward_spamfilter' => $it['spamfilter'] ?? 'pdw',
            ];
            $created[] = $this->kasApiCall($kasLogin, $pw, 'add_mailforward', $payload);
            sleep(1);
        }

        return $created;
    }

    protected function actionAddDnsRecord(array $params, $run)
    {
        $kasLogin = $this->resolveKasLogin($run, $params);
        $pw = $this->kasCredentials[$kasLogin] ?? null;
        if (!$pw) throw new Exception("Missing credentials for {$kasLogin}");

        $payload = [
            'zone_host' => $params['zone_host'] ?? $params['domain_name'] ?? $run->domain_name ?? null,
            'record_name' => $params['record_name'] ?? '',
            'record_type' => $params['record_type'] ?? 'A',
            'record_data' => $params['record_data'] ?? '',
            'record_aux' => $params['record_aux'] ?? 0,
        ];

        return $this->kasApiCall($kasLogin, $pw, 'add_dns_record', $payload);
    }

    protected function actionEnableSsl(array $params, $run)
    {
        // Placeholder. Enabling SSL may require the KAS web UI or a specific API call not documented.
        // Best approach: queue a follow-up job after DNS propagation. For now return a notice.
        return ['queued' => true, 'note' => 'enable_ssl should be queued and executed after DNS propagation'];
    }

    /* ---------- Helpers ---------- */

    protected function resolveKasLogin($run, array $params)
    {
        // priority: params.kas_login > run.kas_login > params.kas_client_id -> map->kas_client -> account_login if available
        if (!empty($params['kas_login'])) return $params['kas_login'];
        if (!empty($run->kas_login)) return $run->kas_login;
        if (!empty($params['kas_client_id'])) {
            // try to lookup client -> account_login if your kas_clients table has that column
            $client = \App\Models\KasClient::find($params['kas_client_id']);
            return $client?->account_login ?? null;
        }
        return null;
    }

    /**
     * Wrapper for calling KAS API KasApi(json)
     *
     * @param string $kasLogin
     * @param string $password
     * @param string $actionName (kas_action)
     * @param array $params - action specific parameters
     * @return array decoded response
     * @throws Exception on permanent failure
     */
    protected function kasApiCall(string $kasLogin, string $password, string $actionName, array $params = []): array
    {
        if (empty($kasLogin) || empty($password)) {
            throw new Exception('Missing KAS credentials for soap call');
        }

        $soap = new SoapClient($this->kasWsdl, ['exceptions' => true, 'cache_wsdl' => WSDL_CACHE_NONE, 'trace' => false]);
        $request = [
            'kas_login' => $kasLogin,
            'kas_auth_type' => 'plain',
            'kas_auth_data' => $password,
            'kas_action' => $actionName,
            'KasRequestParams' => $params
        ];

        $raw = null;
        try {
            $raw = $soap->KasApi(json_encode($request));
            $decoded = $this->normalizeResponse($raw);
            return $decoded;
        } catch (SoapFault $e) {
            $msg = $e->faultstring ?? $e->getMessage();
            // flood protection handling - try once after delay
            if (stripos($msg, 'flood_protection') !== false || stripos($msg, 'flood') !== false) {
                sleep($this->delay + 1);
                try {
                    $raw2 = $soap->KasApi(json_encode($request));
                    $decoded2 = $this->normalizeResponse($raw2);
                    return $decoded2;
                } catch (\Throwable $e2) {
                    throw new Exception("KAS SOAP flood retry failed: " . $e2->getMessage());
                }
            }
            throw new Exception("KAS SOAP error: " . $msg);
        } catch (\Throwable $e) {
            throw new Exception("KAS call failed: " . $e->getMessage());
        }
    }

    protected function normalizeResponse($raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : ['Response' => ['ReturnString' => 'FALSE', 'ReturnInfo' => [], 'Raw' => $raw]];
        }
        return ['Response' => ['ReturnString' => 'FALSE', 'ReturnInfo' => []]];
    }
}
