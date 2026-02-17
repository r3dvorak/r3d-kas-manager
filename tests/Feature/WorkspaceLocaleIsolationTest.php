<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceLocaleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_redirect_keeps_workspace_context(): void
    {
        $workspace = str_repeat('a', 40);

        $response = $this->withHeader('referer', 'https://r3d-kas-manager.test/login?w=' . $workspace)
            ->get('/locale/en?w=' . $workspace);

        $response->assertRedirect('/login?w=' . $workspace);
    }

}
