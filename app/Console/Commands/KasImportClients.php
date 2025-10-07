<?php
/**
 * R3D KAS Manager – Import All-Inkl Accounts
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.16.7-alpha
 * @date      2025-10-07
 * @license   MIT License
 *
 * Imports all KAS accounts (get_accounts) into kas_clients table,
 * sorted by account_login (oldest → newest). Supports --fresh reset.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\KasClient;
use SoapClient;
use Throwable;

class KasImportClients extends Command
{
    protected $signature = 'kas:import-clients 
                            {--dryrun : Show data without saving}
                            {--fresh : Truncate the kas_clients table before importing}';

    protected $description = 'Imports all client accounts from the All-Inkl KAS API into kas_clients table.';

    public function handle(): int
    {
        $this->info('🔎 Fetching KAS accounts...');

        // KAS master credentials (later from config)
        $kasUser = 'w01954e3';
        $kasPass = 'Paad.Int-2023';
        $kasWsdl = 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl';

        try {
            // optional fresh reset
            if ($this->option('fresh')) {
                $this->warn('⚠️ Truncating kas_clients table...');
                KasClient::truncate();
                DB::statement('ALTER TABLE kas_clients AUTO_INCREMENT = 1;');
                $this->info('✅ kas_clients table truncated and AUTO_INCREMENT reset.');
            }

            // connect
            $client = new SoapClient($kasWsdl, [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 25,
            ]);

            // request
            $jsonRequest = json_encode([
                'kas_login'        => $kasUser,
                'kas_auth_type'    => 'plain',
                'kas_auth_data'    => $kasPass,
                'kas_action'       => 'get_accounts',
                'KasRequestParams' => new \stdClass(),
            ], JSON_UNESCAPED_SLASHES);

            $response = $client->KasApi($jsonRequest);
            $data     = json_decode(json_encode($response), true);
            $accounts = $data['Response']['ReturnInfo'] ?? [];

            if (empty($accounts)) {
                $this->error('❌ No ReturnInfo found in API response.');
                return Command::FAILURE;
            }

            // sort by login (oldest → newest)
            usort($accounts, fn($a, $b) => strcmp($a['account_login'] ?? '', $b['account_login'] ?? ''));

            $this->info('✅ Retrieved ' . count($accounts) . ' accounts (sorted by login).');

            $imported = 0;
            $updated  = 0;
            $skipped  = 0;

            foreach ($accounts as $index => $acc) {
                $login = $acc['account_login'] ?? null;
                if (!$login) {
                    $skipped++;
                    continue;
                }

                $record = [
                    'account_login'   => $acc['account_login'] ?? null,
                    'account_password'=> $acc['account_password'] ?? null, // encrypted via mutator
                    'password'        => $acc['account_password'] ?? null, // hashed via mutator
                    'account_comment' => $acc['account_comment'] ?? null,
                    'account_contact_mail' => $acc['account_contact_mail'] ?? null,
                    'max_account'     => (int)($acc['max_account'] ?? 0),
                    'max_domain'      => (int)($acc['max_domain'] ?? 0),
                    'max_subdomain'   => (int)($acc['max_subdomain'] ?? 0),
                    'max_webspace'    => (int)($acc['max_webspace'] ?? 0),
                    'max_mail_account'=> (int)($acc['max_mail_account'] ?? 0),
                    'max_mail_forward'=> (int)($acc['max_mail_forward'] ?? 0),
                    'max_mail_list'   => (int)($acc['max_mail_list'] ?? 0),
                    'max_databases'   => (int)($acc['max_databases'] ?? 0),
                    'max_ftpuser'     => (int)($acc['max_ftpuser'] ?? 0),
                    'max_sambauser'   => (int)($acc['max_sambauser'] ?? 0),
                    'max_cronjobs'    => (int)($acc['max_cronjobs'] ?? 0),
                    'max_wbk'         => (int)($acc['max_wbk'] ?? 0),
                    'inst_htaccess'   => $acc['inst_htaccess'] ?? 'N',
                    'inst_fpse'       => $acc['inst_fpse'] ?? 'N',
                    'inst_software'   => $acc['inst_software'] ?? 'N',
                    'kas_access_forbidden' => $acc['kas_access_forbidden'] ?? 'N',
                    'logging'         => $acc['logging'] ?? null,
                    'statistic'       => $acc['statistic'] ?? null,
                    'logage'          => (int)($acc['logage'] ?? 0),
                    'show_password'   => $acc['show_password'] ?? 'N',
                    'dns_settings'    => $acc['dns_settings'] ?? 'N',
                    'show_direct_links'             => $acc['show_direct_links'] ?? null,
                    'ssh_access'                    => $acc['ssh_access'] ?? 'N',
                    'used_account_space'            => (float)($acc['used_account_space'] ?? 0),
                    'account_2fa'                   => $acc['account_2fa'] ?? null,
                    'show_direct_links_wbk'         => $acc['show_direct_links_wbk'] ?? 'N',
                    'show_direct_links_sambausers'  => $acc['show_direct_links_sambausers'] ?? 'N',
                    'show_direct_links_accounts'    => $acc['show_direct_links_accounts'] ?? 'N',
                    'show_direct_links_mailaccounts'=> $acc['show_direct_links_mailaccounts'] ?? 'N',
                    'show_direct_links_ftpuser'     => $acc['show_direct_links_ftpuser'] ?? 'N',
                    'show_direct_links_databases'   => $acc['show_direct_links_databases'] ?? 'N',
                    'in_progress'                   => ($acc['in_progress'] ?? 'FALSE') === 'TRUE' ? 1 : 0,
                ];

                if ($this->option('dryrun')) {
                    $this->line(sprintf("[%02d] %s  →  %s", $index + 1, $record['account_login'], $record['account_comment']));
                    continue;
                }

                $existing = KasClient::where('account_login', $record['account_login'])->first();
                if ($existing) {
                    $existing->update($record);
                    $updated++;
                } else {
                    KasClient::create($record);
                    $imported++;
                }
            }

            if ($this->option('dryrun')) {
                $this->info('💡 Dry-run complete. No database changes made.');
            } else {
                $this->newLine();
                $this->info('✅ Client import complete!');
                $this->line("   Imported new:     {$imported}");
                $this->line("   Updated existing: {$updated}");
                $this->line("   Skipped invalid:  {$skipped}");
            }

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
