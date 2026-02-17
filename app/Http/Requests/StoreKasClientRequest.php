<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKasClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'account_comment' => 'required|string|max:255',
            'account_login' => 'required|string|max:20|unique:kas_clients,account_login',
            'account_contact_mail' => 'nullable|email|max:255',
            'server_internal_domain' => 'nullable|string|max:255',
            'server_hostname' => 'nullable|string|max:255',
            'server_ip' => 'nullable|string|max:45',
            'all_inkl_customer_number' => 'nullable|string|max:32',
            'all_inkl_contract_number' => 'nullable|string|max:32',
            'preferred_locale' => 'nullable|in:de,en',
            'client_menu_items_present' => 'nullable|in:1',
            'client_menu_items' => 'nullable|array',
            'client_menu_items.*' => 'string|in:dashboard,domain,subdomain,mailboxes,mailforwards,ftp,databases,dns,ssl,statistics,recipes',
        ];
    }
}
