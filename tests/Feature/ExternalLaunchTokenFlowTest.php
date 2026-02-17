<?php

namespace Tests\Feature;

use App\Models\ExternalLaunchToken;
use App\Models\KasClient;
use App\Models\KasDatabase;
use App\Models\KasMailAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExternalLaunchTokenFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_launch_token_and_consume_once(): void
    {
        $client = KasClient::create([
            'account_login' => 'wlaunch01',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Launch Client',
        ]);

        $workspace = str_repeat('f', 40);

        $create = $this->actingAs($client, 'kas_client')->get('/client/launch/pma?w=' . $workspace);
        $create->assertRedirect();

        $location = (string) $create->headers->get('Location');
        $this->assertMatchesRegularExpression('#/launch/[A-Za-z0-9]{64}\?w=[a-f0-9]{40}$#', $location);

        preg_match('#/launch/([A-Za-z0-9]{64})#', $location, $m);
        $this->assertNotEmpty($m[1] ?? null);
        $token = (string) $m[1];

        $this->assertDatabaseHas('external_launch_tokens', [
            'kas_client_id' => $client->id,
            'tool' => 'pma',
            'used_at' => null,
        ]);
        $this->assertDatabaseHas('external_launch_audits', [
            'event' => 'created',
            'tool' => 'pma',
            'kas_client_id' => $client->id,
        ]);

        $consume = $this->get('/launch/' . $token . '?w=' . $workspace);
        $consume->assertRedirect('https://wlaunch01.kasserver.com/mysqladmin/PMA5/index.php');

        $tokenRecord = ExternalLaunchToken::query()->where('kas_client_id', $client->id)->first();
        $this->assertNotNull($tokenRecord?->used_at);
        $this->assertDatabaseHas('external_launch_audits', [
            'event' => 'consumed',
            'tool' => 'pma',
            'kas_client_id' => $client->id,
        ]);

        $secondConsume = $this->get('/launch/' . $token . '?w=' . $workspace);
        $secondConsume->assertForbidden();
        $this->assertDatabaseHas('external_launch_audits', [
            'event' => 'denied',
            'tool' => 'pma',
            'kas_client_id' => $client->id,
        ]);
    }

    public function test_expired_token_is_rejected(): void
    {
        $client = KasClient::create([
            'account_login' => 'wlaunch02',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Launch Client 2',
        ]);

        $create = $this->actingAs($client, 'kas_client')->get('/client/launch/webmail?w=' . str_repeat('a', 40));
        $create->assertRedirect();
        $location = (string) $create->headers->get('Location');
        preg_match('#/launch/([A-Za-z0-9]{64})#', $location, $m);
        $token = (string) ($m[1] ?? '');
        $this->assertNotSame('', $token);

        ExternalLaunchToken::query()->where('kas_client_id', $client->id)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $consume = $this->get('/launch/' . $token);
        $consume->assertForbidden();
    }

    public function test_launch_context_must_belong_to_authenticated_client(): void
    {
        $clientA = KasClient::create([
            'account_login' => 'wlaunch03',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Launch Client 3',
        ]);

        $clientB = KasClient::create([
            'account_login' => 'wlaunch04',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Launch Client 4',
        ]);

        $mailboxOfB = KasMailAccount::create([
            'kas_login' => 'wlaunch04',
            'mail_login' => 'office',
            'domain' => 'example.test',
            'email' => 'office@example.test',
            'status' => 'active',
            'client_id' => $clientB->id,
        ]);

        $dbOfB = KasDatabase::create([
            'kas_login' => 'wlaunch04',
            'client_id' => $clientB->id,
            'database_login' => 'wlaunch04_db1',
            'database_comment' => 'DB B',
            'database_allowed_hosts' => '%',
            'status' => 'active',
        ]);

        $mailboxLaunch = $this->actingAs($clientA, 'kas_client')->get('/client/launch/webmail?mailbox=' . $mailboxOfB->id . '&w=' . str_repeat('a', 40));
        $mailboxLaunch->assertNotFound();

        $dbLaunch = $this->actingAs($clientA, 'kas_client')->get('/client/launch/pma?database=' . $dbOfB->id . '&w=' . str_repeat('b', 40));
        $dbLaunch->assertNotFound();
    }

    public function test_consume_requires_matching_workspace_when_token_is_workspace_bound(): void
    {
        $client = KasClient::create([
            'account_login' => 'wlaunch05',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Launch Client 5',
        ]);

        $workspace = str_repeat('c', 40);

        $create = $this->actingAs($client, 'kas_client')->get('/client/launch/webmail?w=' . $workspace);
        $create->assertRedirect();
        $location = (string) $create->headers->get('Location');
        preg_match('#/launch/([A-Za-z0-9]{64})#', $location, $m);
        $token = (string) ($m[1] ?? '');
        $this->assertNotSame('', $token);

        $wrongWorkspaceConsume = $this->get('/launch/' . $token . '?w=' . str_repeat('d', 40));
        $wrongWorkspaceConsume->assertForbidden();
    }
}
