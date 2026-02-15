<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasMailForward extends Model
{
    protected $table = 'kas_mailforwards';

    protected $fillable = [
        'kas_login',
        'mail_forward_address',
        'mail_forward_targets',
        'mail_forward_comment',
        'mail_forward_spamfilter',
        'in_progress',
        'status',
        'domain_id',
        'client_id',
        'data_json',
    ];

    protected $casts = [
        'in_progress' => 'boolean',
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

    public function spamfilterEnabled(): bool
    {
        $v = (string) ($this->mail_forward_spamfilter ?? '');
        $v = strtoupper(trim($v));
        return $v !== '' && $v !== 'N' && $v !== '0';
    }
}

