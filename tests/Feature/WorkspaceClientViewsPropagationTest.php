<?php

namespace Tests\Feature;

use App\Models\KasClient;
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
}
