<?php

namespace Tests\Feature;

use App\Models\KasClient;
use App\Models\KasMailAccount;
use App\Models\KasMailForward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMailCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_delete_mailbox(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'admin_mail',
            'email' => 'admin_mail@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        KasClient::create([
            'account_login' => 'w01mailcrud',
            'account_comment' => 'Mail CRUD',
            'password' => 'secret123',
        ]);

        $this->actingAs($admin, 'web');

        $create = $this->post('/mailboxes', [
            'kas_login' => 'w01mailcrud',
            'domain' => 'example.test',
            'local_part' => 'info',
            'mail_login' => 'info',
            'status' => 'active',
            'quota_mb' => 512,
            'used_kb' => 32,
            'spamfilter' => 'mark',
        ]);
        $create->assertRedirect('/mailboxes?kas_login=w01mailcrud');

        $mailbox = KasMailAccount::where('kas_login', 'w01mailcrud')->firstOrFail();
        $this->assertDatabaseHas('kas_mailaccounts', [
            'id' => $mailbox->id,
            'email' => 'info@example.test',
        ]);

        $update = $this->put('/mailboxes/' . $mailbox->id, [
            'kas_login' => 'w01mailcrud',
            'domain' => 'example.test',
            'local_part' => 'support',
            'mail_login' => 'support',
            'status' => 'active',
            'quota_mb' => 1024,
            'used_kb' => 64,
            'spamfilter' => 'reject',
        ]);
        $update->assertRedirect('/mailboxes?kas_login=w01mailcrud');
        $this->assertDatabaseHas('kas_mailaccounts', [
            'id' => $mailbox->id,
            'email' => 'support@example.test',
            'mail_login' => 'support',
        ]);

        $delete = $this->delete('/mailboxes/' . $mailbox->id);
        $delete->assertRedirect('/mailboxes?kas_login=w01mailcrud');
        $this->assertDatabaseMissing('kas_mailaccounts', ['id' => $mailbox->id]);
    }

    public function test_admin_can_create_update_delete_mailforward(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'login' => 'admin_fwd',
            'email' => 'admin_fwd@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        KasClient::create([
            'account_login' => 'w01fwdcrud',
            'account_comment' => 'Forward CRUD',
            'password' => 'secret123',
        ]);

        $this->actingAs($admin, 'web');

        $create = $this->post('/mailforwards', [
            'kas_login' => 'w01fwdcrud',
            'mail_forward_address' => 'info@example.test',
            'mail_forward_targets' => 'a@example.test,b@example.test',
            'mail_forward_comment' => 'Initial',
            'mail_forward_spamfilter' => 'Y',
            'status' => 'active',
            'in_progress' => 0,
        ]);
        $create->assertRedirect('/mailforwards?kas_login=w01fwdcrud');

        $forward = KasMailForward::where('kas_login', 'w01fwdcrud')->firstOrFail();
        $this->assertDatabaseHas('kas_mailforwards', [
            'id' => $forward->id,
            'mail_forward_address' => 'info@example.test',
        ]);

        $update = $this->put('/mailforwards/' . $forward->id, [
            'kas_login' => 'w01fwdcrud',
            'mail_forward_address' => 'support@example.test',
            'mail_forward_targets' => 'z@example.test',
            'mail_forward_comment' => 'Updated',
            'mail_forward_spamfilter' => 'N',
            'status' => 'active',
            'in_progress' => 1,
        ]);
        $update->assertRedirect('/mailforwards?kas_login=w01fwdcrud');
        $this->assertDatabaseHas('kas_mailforwards', [
            'id' => $forward->id,
            'mail_forward_address' => 'support@example.test',
            'mail_forward_targets' => 'z@example.test',
        ]);

        $delete = $this->delete('/mailforwards/' . $forward->id);
        $delete->assertRedirect('/mailforwards?kas_login=w01fwdcrud');
        $this->assertDatabaseMissing('kas_mailforwards', ['id' => $forward->id]);
    }

    public function test_non_admin_cannot_access_admin_mail_crud_routes(): void
    {
        $user = User::create([
            'name' => 'User',
            'login' => 'user_mail',
            'email' => 'user_mail@example.test',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'is_admin' => 0,
        ]);

        $this->actingAs($user, 'web');

        $this->get('/mailboxes/create')->assertForbidden();
        $this->post('/mailboxes', [
            'kas_login' => 'w01none',
            'domain' => 'example.test',
            'local_part' => 'x',
            'status' => 'active',
        ])->assertForbidden();

        $this->get('/mailforwards/create')->assertForbidden();
        $this->post('/mailforwards', [
            'kas_login' => 'w01none',
            'mail_forward_address' => 'a@example.test',
            'mail_forward_targets' => 'b@example.test',
            'status' => 'active',
        ])->assertForbidden();
    }
}
