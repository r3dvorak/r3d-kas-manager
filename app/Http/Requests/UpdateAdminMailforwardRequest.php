<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminMailforwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'kas_login' => 'required|string|max:32|exists:kas_clients,account_login',
            'mail_forward_address' => 'required|string|max:255',
            'mail_forward_targets' => 'required|string|max:4000',
            'mail_forward_comment' => 'nullable|string|max:255',
            'mail_forward_spamfilter' => 'nullable|string|max:64',
            'status' => 'required|string|in:active,missing',
            'in_progress' => 'nullable|boolean',
        ];
    }
}
