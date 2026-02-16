<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateClientMailboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('kas_client')->check();
    }

    public function rules(): array
    {
        return [
            'domain_id' => 'required|integer|exists:kas_domains,id',
            'local_part' => 'required|string|max:128|regex:/^[A-Za-z0-9._%+\\-]+$/',
            'mail_login' => 'nullable|string|max:128',
            'status' => 'required|string|in:active,missing',
            'quota_mb' => 'nullable|numeric|min:0',
            'used_kb' => 'nullable|numeric|min:0',
            'spamfilter' => 'nullable|string|max:64',
        ];
    }
}
