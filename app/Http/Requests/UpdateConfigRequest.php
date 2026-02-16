<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'session_timeout' => 'nullable|integer|min:5|max:240',
            'absolute_session_max' => 'nullable|integer|min:30|max:1440',
            'support_email' => 'nullable|email',
            'site_name' => 'nullable|string|max:255',
            'logo_file' => 'nullable|file|mimes:svg,png,jpg,jpeg,gif|max:1024',
            'all_inkl_customer_number' => 'nullable|string|max:32',
            'all_inkl_contract_number' => 'nullable|string|max:32',
            'all_inkl_members_login' => 'nullable|string|max:32',
            'all_inkl_monitor_login' => 'nullable|string|max:255',
            'hints_show_public_ip' => 'nullable',
            'hints_show_account_data' => 'nullable',
            'hints_show_env_versions' => 'nullable',
            'hints_show_last_reports' => 'nullable',
            'hints_show_kas_status' => 'nullable',
            'hints_show_quick_links' => 'nullable',
            'hints_show_context_help' => 'nullable',
            'mail_standardfilter_default' => 'nullable|string|max:512',
        ];
    }
}
