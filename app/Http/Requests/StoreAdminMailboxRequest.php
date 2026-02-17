<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminMailboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'kas_login' => 'required|string|max:32|exists:kas_clients,account_login',
            'domain' => 'required|string|max:255',
            'local_part' => 'required|string|max:128|regex:/^[A-Za-z0-9._%+\\-]+$/',
            'mail_login' => 'nullable|string|max:128',
            'status' => 'required|string|in:active,missing',
            'mailbox_access_state' => 'nullable|string|in:enabled,receive_disabled,blocked',
            'quota_mb' => 'nullable|numeric|min:0',
            'used_kb' => 'nullable|numeric|min:0',
            'spamfilter' => 'nullable|string|max:64',
        ];
    }
}
