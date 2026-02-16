<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateClientDnsRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('kas_client')->check();
    }

    public function rules(): array
    {
        return [
            'domain_id' => 'required|integer|exists:kas_domains,id',
            'record_type' => 'required|string|max:16|in:A,AAAA,CNAME,MX,TXT,SRV,NS,CAA',
            'record_name' => 'nullable|string|max:255',
            'record_data' => 'required|string|max:2000',
            'record_aux' => 'nullable|integer|min:0|max:65535',
        ];
    }
}
