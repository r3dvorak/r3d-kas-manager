<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasMailAccount extends Model
{
    protected $table = 'kas_mailaccounts';

    protected $fillable = [
        'kas_login',
        'mail_login',
        'domain',
        'email',
        'status',
        'data_json',
        'domain_id',
        'client_id',
    ];

    protected $casts = [
        'data_json' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(KasClient::class, 'client_id');
    }

    public function domainModel()
    {
        return $this->belongsTo(KasDomain::class, 'domain_id');
    }

    public function spamfilterLabel(): string
    {
        $v = (string) ($this->data_json['mail_spamfilter'] ?? '');
        $v = trim($v);
        return $v === '' ? '—' : $v;
    }

    public function quotaRule(): ?string
    {
        $v = $this->data_json['quota_rule'] ?? null;
        return is_string($v) ? $v : null;
    }

    public function usedSpaceMb(): ?float
    {
        $v = $this->data_json['used_mailaccount_space'] ?? null;
        if ($v === null || $v === '') return null;
        // KAS returns mailbox usage in KB.
        // Convert to MB for display/aggregation.
        return is_numeric($v) ? round(((float) $v) / 1024, 2) : null;
    }
}
