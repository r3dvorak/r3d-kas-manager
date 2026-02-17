<?php

namespace Tests\Feature;

use App\Models\KasDatabase;
use App\Models\KasClient;
use App\Models\KasMailAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkspaceClientViewsPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_statistics_view_keeps_workspace_in_actions_and_locale_links(): void
    {
        $client = KasClient::create([
            'account_login' => 'wtest001',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Test Client',
            'preferred_locale' => 'en',
        ]);

        $workspace = str_repeat('d', 40);

        $response = $this->actingAs($client, 'kas_client')->get('/client/statistics?w=' . $workspace);
        $response->assertOk();

        $response->assertSee('/client/statistics/preview?w=' . $workspace, false);
        $response->assertSee('/client/statistics/sync?w=' . $workspace, false);
        $response->assertSee('/locale/de?w=' . $workspace, false);
        $response->assertSee('/locale/en?w=' . $workspace, false);
    }

    public function test_mailbox_and_database_rows_render_per_item_launch_links(): void
    {
        $client = KasClient::create([
            'account_login' => 'wtest002',
            'password' => Hash::make('secret123'),
            'account_comment' => 'Test Client 2',
        ]);

        $mailbox = KasMailAccount::create([
            'kas_login' => 'wtest002',
            'mail_login' => 'info',
            'domain' => 'example.test',
            'email' => 'info@example.test',
            'status' => 'active',
            'client_id' => $client->id,
        ]);

        $database = KasDatabase::create([
            'kas_login' => 'wtest002',
            'client_id' => $client->id,
            'database_login' => 'wtest002_db1',
            'database_comment' => 'DB1',
            'database_allowed_hosts' => '%',
            'status' => 'active',
        ]);

        $workspace = str_repeat('e', 40);

        $mailboxes = $this->actingAs($client, 'kas_client')->get('/client/mailboxes?w=' . $workspace);
        $mailboxes->assertOk();
        $mailboxes->assertSee('/client/launch/webmail', false);
        $mailboxes->assertSee('mailbox=' . $mailbox->id, false);
        $mailboxes->assertSee('w=' . $workspace, false);

        $databases = $this->actingAs($client, 'kas_client')->get('/client/databases?w=' . $workspace);
        $databases->assertOk();
        $databases->assertSee('/client/launch/pma', false);
        $databases->assertSee('database=' . $database->id, false);
        $databases->assertSee('w=' . $workspace, false);
    }
}
