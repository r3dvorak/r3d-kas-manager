<?php

namespace App\Services\Kas;

use App\Models\KasClient;
use App\Models\KasDomain;
use App\Services\Recipes\KasGateway;
use Illuminate\Support\Facades\DB;

class MailSnapshotService
{
    public function __construct(private KasGateway $kas) {}

    /**
     * @return array{remote_emails:array<string,bool>,local_emails:array<string,bool>,to_add:list<string>,to_remove:list<string>}
     */
    public function previewMailboxes(string $kasLogin): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $remote = $this->remoteMailboxEmails($kasLogin);
        $local = $this->localMailboxEmails($kasLogin);

        $toAdd = array_values(array_diff(array_keys($remote), array_keys($local)));
        sort($toAdd);
        $toRemove = array_values(array_diff(array_keys($local), array_keys($remote)));
        sort($toRemove);

        return [
            'remote_emails' => $remote,
            'local_emails' => $local,
            'to_add' => $toAdd,
            'to_remove' => $toRemove,
        ];
    }

    /**
     * @return array{remote_from:array<string,bool>,local_from:array<string,bool>,to_add:list<string>,to_remove:list<string>}
     */
    public function previewMailforwards(string $kasLogin): array
    {
        $kasLogin = strtolower(trim($kasLogin));

        $remote = $this->remoteForwardAddresses($kasLogin);
        $local = $this->localForwardAddresses($kasLogin);

        $toAdd = array_values(array_diff(array_keys($remote), array_keys($local)));
        sort($toAdd);
        $toRemove = array_values(array_diff(array_keys($local), array_keys($remote)));
        sort($toRemove);

        return [
            'remote_from' => $remote,
            'local_from' => $local,
            'to_add' => $toAdd,
            'to_remove' => $toRemove,
        ];
    }

    public function syncMailboxes(string $kasLogin): array
    {
        $kasLogin = strtolower(trim($kasLogin));
        $client = KasClient::where('account_login', $kasLogin)->first();
        $clientId = $client?->id;

        $accounts = $this->kas->fetchMailaccounts($kasLogin);

        // Replace snapshot for this login.
        DB::table('kas_mailaccounts')->where('kas_login', $kasLogin)->delete();

        $now = now();
        $inserted = 0;

        foreach ($accounts as $a) {
            $internal = strtolower(trim((string) ($a['mail_login'] ?? '')));
            if ($internal === '') continue;

            $addrs = (string) ($a['mail_addresses'] ?? $a['mail_adresses'] ?? '');
            $email = $this->firstEmail($addrs);
            $domain = $email ? (explode('@', $email, 2)[1] ?? null) : null;

            $domainId = null;
            if ($domain) {
                $dm = KasDomain::where('domain_full', $domain)->whereNull('deleted_at')->first();
                $domainId = $dm?->id;
            }

            DB::table('kas_mailaccounts')->insert([
                'kas_login' => $kasLogin,
                'mail_login' => $internal,
                'domain' => $domain,
                'email' => $addrs,
                'status' => 'active',
                'data_json' => json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'domain_id' => $domainId,
                'client_id' => $clientId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        return ['inserted' => $inserted];
    }

    public function syncMailforwards(string $kasLogin): array
    {
        $kasLogin = strtolower(trim($kasLogin));
        $client = KasClient::where('account_login', $kasLogin)->first();
        $clientId = $client?->id;

        $forwards = $this->kas->fetchMailforwards($kasLogin);

        DB::table('kas_mailforwards')->where('kas_login', $kasLogin)->delete();

        $now = now();
        $inserted = 0;

        foreach ($forwards as $f) {
            $addr = (string) ($f['mail_forward_address'] ?? $f['mail_forward_adress'] ?? $f['mail_forward'] ?? '');
            $addr = strtolower(trim($addr));
            if ($addr === '' || !str_contains($addr, '@')) continue;
            $domain = explode('@', $addr, 2)[1] ?? null;

            $targets = $f['mail_forward_targets'] ?? '';
            if (is_array($targets)) $targets = implode(',', $targets);

            $domainId = null;
            if ($domain) {
                $dm = KasDomain::where('domain_full', $domain)->whereNull('deleted_at')->first();
                $domainId = $dm?->id;
            }

            DB::table('kas_mailforwards')->insert([
                'kas_login' => $kasLogin,
                'mail_forward_address' => $addr,
                'mail_forward_targets' => (string) $targets,
                'mail_forward_comment' => (string) ($f['mail_forward_comment'] ?? ''),
                'mail_forward_spamfilter' => (string) ($f['mail_forward_spamfilter'] ?? ''),
                'in_progress' => ((string) ($f['in_progress'] ?? 'N')) === 'Y',
                'status' => 'active',
                'domain_id' => $domainId,
                'client_id' => $clientId,
                'data_json' => json_encode($f, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        return ['inserted' => $inserted];
    }

    /** @return array<string,bool> */
    private function remoteMailboxEmails(string $kasLogin): array
    {
        $out = [];
        $accounts = $this->kas->fetchMailaccounts($kasLogin);
        foreach ($accounts as $a) {
            $addrs = (string) ($a['mail_addresses'] ?? $a['mail_adresses'] ?? '');
            foreach ($this->extractEmails($addrs) as $e) {
                $out[$e] = true;
            }
        }
        ksort($out);
        return $out;
    }

    /** @return array<string,bool> */
    private function localMailboxEmails(string $kasLogin): array
    {
        $out = [];
        $rows = DB::table('kas_mailaccounts')->where('kas_login', $kasLogin)->pluck('email')->all();
        foreach ($rows as $addrs) {
            foreach ($this->extractEmails((string) $addrs) as $e) {
                $out[$e] = true;
            }
        }
        ksort($out);
        return $out;
    }

    /** @return array<string,bool> */
    private function remoteForwardAddresses(string $kasLogin): array
    {
        $out = [];
        $forwards = $this->kas->fetchMailforwards($kasLogin);
        foreach ($forwards as $f) {
            $addr = (string) ($f['mail_forward_address'] ?? $f['mail_forward_adress'] ?? $f['mail_forward'] ?? '');
            $addr = strtolower(trim($addr));
            if ($addr !== '') $out[$addr] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return array<string,bool> */
    private function localForwardAddresses(string $kasLogin): array
    {
        $out = [];
        $rows = DB::table('kas_mailforwards')->where('kas_login', $kasLogin)->pluck('mail_forward_address')->all();
        foreach ($rows as $addr) {
            $addr = strtolower(trim((string) $addr));
            if ($addr !== '') $out[$addr] = true;
        }
        ksort($out);
        return $out;
    }

    /** @return list<string> */
    private function extractEmails(string $raw): array
    {
        $raw = strtolower($raw);
        $parts = preg_split('/[\\s,;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if ($p === '' || !str_contains($p, '@')) continue;
            $out[] = $p;
        }
        return array_values(array_unique($out));
    }

    private function firstEmail(string $raw): ?string
    {
        $list = $this->extractEmails($raw);
        return $list[0] ?? null;
    }
}

