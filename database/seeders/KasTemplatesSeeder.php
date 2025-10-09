<?php
/**
 * Seeder: KasTemplatesSeeder
 *
 * @version 0.24.0
 * @date    2025-10-09
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KasTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Entferne evtl. gleiche Einträge (idempotent)
        DB::table('kas_templates')->where('template_type', 'dns')->where('template_name', 'standard-web')->delete();
        DB::table('kas_templates')->where('template_type', 'dns')->where('template_name', 'mail-only')->delete();
        DB::table('kas_templates')->where('template_type', 'mail')->where('template_name', 'basic-mail')->delete();
        DB::table('kas_templates')->where('template_type', 'php')->where('template_name', 'php-8.3')->delete();
        DB::table('kas_templates')->where('template_type', 'ssl')->where('template_name', 'letsencrypt-auto')->delete();
        DB::table('kas_templates')->where('template_type', 'dkim')->where('template_name', 'dkim-default')->delete();

        // 1) DNS: standard-web
        DB::table('kas_templates')->insert([
            'template_type' => 'dns',
            'template_name' => 'standard-web',
            'description'   => 'Standard web + mail: A (root + *), MX -> KAS mailserver, SPF, DMARC, DKIM placeholder',
            'data_json'     => json_encode([
                ['record_name' => '',   'record_type' => 'A',   'record_data' => '178.63.15.195'],
                ['record_name' => '*',  'record_type' => 'A',   'record_data' => '178.63.15.195'],
                ['record_name' => '',   'record_type' => 'MX',  'record_data' => 'w01e77bc.kasserver.com.', 'record_aux' => 10],
                ['record_name' => '',   'record_type' => 'TXT', 'record_data' => 'v=spf1 mx a ?all'],
                ['record_name' => '_dmarc','record_type' => 'TXT','record_data' => 'v=DMARC1; p=none;'],
                ['record_name' => 'kas{YEAR}._domainkey','record_type' => 'TXT','record_data' => 'v=DKIM1; k=rsa; p=REPLACE_ME']
            ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'kas_client_id' => null,
            'created_by'    => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // 2) DNS: mail-only
        DB::table('kas_templates')->insert([
            'template_type' => 'dns',
            'template_name' => 'mail-only',
            'description'   => 'Only mail: MX + SPF + DKIM placeholder',
            'data_json'     => json_encode([
                ['record_name' => '',  'record_type' => 'MX',  'record_data' => 'w01e77bc.kasserver.com.', 'record_aux' => 10],
                ['record_name' => '',  'record_type' => 'TXT', 'record_data' => 'v=spf1 mx -all'],
                ['record_name' => 'kas{YEAR}._domainkey','record_type' => 'TXT','record_data' => 'v=DKIM1; k=rsa; p=REPLACE_ME']
            ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3) Mail template: basic-mail (example mailbox list)
        DB::table('kas_templates')->insert([
            'template_type' => 'mail',
            'template_name' => 'basic-mail',
            'description'   => 'Creates typical mailbox list used by recipes',
            'data_json'     => json_encode([
                'mailboxes' => [
                    ['local' => 'info',    'quota_mb' => 1024],
                    ['local' => 'kontakt', 'quota_mb' => 1024],
                    ['local' => 'support', 'quota_mb' => 2048],
                    ['local' => 'office',  'quota_mb' => 1024],
                    ['local' => 'admin',   'quota_mb' => 512]
                ],
                'default_password' => 'auto'
            ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4) PHP template: php-8.3
        DB::table('kas_templates')->insert([
            'template_type' => 'php',
            'template_name' => 'php-8.3',
            'description'   => 'Set PHP runtime to 8.3',
            'data_json'     => json_encode(['php_version' => '8.3'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5) SSL template: letsencrypt-auto
        DB::table('kas_templates')->insert([
            'template_type' => 'ssl',
            'template_name' => 'letsencrypt-auto',
            'description'   => 'Enable Let\'s Encrypt with automatic renewal',
            'data_json'     => json_encode(['provider' => 'letsencrypt', 'auto_renew' => true], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6) DKIM helper
        DB::table('kas_templates')->insert([
            'template_type' => 'dkim',
            'template_name' => 'dkim-default',
            'description'   => 'DKIM selector template placeholder – recipe should replace selector + key',
            'data_json'     => json_encode(['selector_pattern' => 'kas{YEAR}{RAND}', 'public_key' => 'REPLACE_ME'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
