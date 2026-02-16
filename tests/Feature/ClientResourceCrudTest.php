<?php

namespace Tests\Feature;

use App\Models\KasClient;
use App\Models\KasDnsRecord;
use App\Models\KasDomain;
use App\Models\KasMailAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientResourceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_update_and_delete_dns_record(): void
    {
        $client = KasClient::create([
            'account_login' => 'w01clienta',
            'account_comment' => 'Client A',
            'password' => 'secret123',
        ]);

        $domain = KasDomain::create([
            'kas_client_id' => $client->id,
            'domain_name' => 'example',
            'domain_tld' => 'test',
            'domain_full' => 'example.test',
            'domain_path' => '/www/htdocs/w01clienta/example.test',
        ]);

        $this->actingAs($client, 'kas_client');

        $create = $this->post('/client/dns', [
            'domain_id' => $domain->id,
            'record_type' => 'A',
            'record_name' => 'www',
            'record_data' => '127.0.0.1',
            'record_aux' => 0,
        ]);
        $create->assertRedirect('/client/dns');

        $record = KasDnsRecord::where('domain_id', $domain->id)->firstOrFail();
        $this->assertDatabaseHas('kas_dns_records', [
            'id' => $record->id,
            'record_name' => 'www',
            'record_data' => '127.0.0.1',
        ]);

        $update = $this->put('/client/dns/' . $record->id, [
            'domain_id' => $domain->id,
            'record_type' => 'A',
            'record_name' => 'api',
            'record_data' => '127.0.0.2',
            'record_aux' => 0,
        ]);
        $update->assertRedirect('/client/dns');
        $this->assertDatabaseHas('kas_dns_records', [
            'id' => $record->id,
            'record_name' => 'api',
            'record_data' => '127.0.0.2',
        ]);

        $delete = $this->delete('/client/dns/' . $record->id);
        $delete->assertRedirect('/client/dns');
        $this->assertDatabaseMissing('kas_dns_records', ['id' => $record->id]);
    }

    public function test_client_cannot_modify_dns_record_of_other_client(): void
    {
        $owner = KasClient::create([
            'account_login' => 'w01owner',
            'account_comment' => 'Owner',
            'password' => 'secret123',
        ]);
        $intruder = KasClient::create([
            'account_login' => 'w01intruder',
            'account_comment' => 'Intruder',
            'password' => 'secret123',
        ]);

        $ownerDomain = KasDomain::create([
            'kas_client_id' => $owner->id,
            'domain_name' => 'owner',
            'domain_tld' => 'test',
            'domain_full' => 'owner.test',
            'domain_path' => '/www/htdocs/w01owner/owner.test',
        ]);

        $record = KasDnsRecord::create([
            'kas_login' => 'w01owner',
            'domain_id' => $ownerDomain->id,
            'record_zone' => 'owner.test',
            'record_name' => 'www',
            'record_type' => 'A',
            'record_data' => '127.0.0.1',
            'record_aux' => 0,
        ]);

        $this->actingAs($intruder, 'kas_client');

        $response = $this->put('/client/dns/' . $record->id, [
            'domain_id' => $ownerDomain->id,
            'record_type' => 'A',
            'record_name' => 'hack',
            'record_data' => '127.0.0.9',
            'record_aux' => 0,
        ]);

        $response->assertNotFound();
    }

    public function test_client_can_create_update_and_delete_mailbox(): void
    {
        $client = KasClient::create([
            'account_login' => 'w01maila',
            'account_comment' => 'Client A',
            'password' => 'secret123',
        ]);

        $domain = KasDomain::create([
            'kas_client_id' => $client->id,
            'domain_name' => 'mail',
            'domain_tld' => 'test',
            'domain_full' => 'mail.test',
            'domain_path' => '/www/htdocs/w01maila/mail.test',
        ]);

        $this->actingAs($client, 'kas_client');

        $create = $this->post('/client/mailboxes', [
            'domain_id' => $domain->id,
            'local_part' => 'info',
            'mail_login' => 'info',
            'status' => 'active',
            'quota_mb' => 1024,
            'used_kb' => 256,
            'spamfilter' => 'mark',
        ]);
        $create->assertRedirect('/client/mailboxes');

        $mailbox = KasMailAccount::where('domain_id', $domain->id)->firstOrFail();
        $this->assertDatabaseHas('kas_mailaccounts', [
            'id' => $mailbox->id,
            'email' => 'info@mail.test',
            'kas_login' => 'w01maila',
        ]);

        $update = $this->put('/client/mailboxes/' . $mailbox->id, [
            'domain_id' => $domain->id,
            'local_part' => 'support',
            'mail_login' => 'support',
            'status' => 'active',
            'quota_mb' => 2048,
            'used_kb' => 512,
            'spamfilter' => 'reject',
        ]);
        $update->assertRedirect('/client/mailboxes');

        $this->assertDatabaseHas('kas_mailaccounts', [
            'id' => $mailbox->id,
            'email' => 'support@mail.test',
            'mail_login' => 'support',
        ]);

        $delete = $this->delete('/client/mailboxes/' . $mailbox->id);
        $delete->assertRedirect('/client/mailboxes');
        $this->assertDatabaseMissing('kas_mailaccounts', ['id' => $mailbox->id]);
    }

    public function test_client_cannot_modify_mailbox_of_other_client(): void
    {
        $owner = KasClient::create([
            'account_login' => 'w01mailowner',
            'account_comment' => 'Owner',
            'password' => 'secret123',
        ]);
        $intruder = KasClient::create([
            'account_login' => 'w01mailintruder',
            'account_comment' => 'Intruder',
            'password' => 'secret123',
        ]);

        $mailbox = KasMailAccount::create([
            'kas_login' => 'w01mailowner',
            'mail_login' => 'info',
            'domain' => 'owner.test',
            'email' => 'info@owner.test',
            'status' => 'active',
            'data_json' => [],
        ]);

        $this->actingAs($intruder, 'kas_client');

        $response = $this->delete('/client/mailboxes/' . $mailbox->id);
        $response->assertNotFound();
    }
}
