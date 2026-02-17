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

    /**
     * Canonical mailbox access state used for UI rendering and toggle actions.
     * Values: enabled | receive_disabled | blocked
     */
    public function mailboxAccessState(): string
    {
        $raw = strtolower(trim((string) (
            $this->data_json['mailbox_access_state']
            ?? $this->data_json['mailbox_status']
            ?? ''
        )));

        $map = [
            'aktiviert' => 'enabled',
            'active' => 'enabled',
            'enabled' => 'enabled',
            'e-mail-empfang deaktiviert' => 'receive_disabled',
            'mail receive disabled' => 'receive_disabled',
            'receive_disabled' => 'receive_disabled',
            'disabled' => 'receive_disabled',
            'gesperrt' => 'blocked',
            'blocked' => 'blocked',
            'ban' => 'blocked',
            'locked' => 'blocked',
        ];

        if ($raw !== '' && isset($map[$raw])) {
            return $map[$raw];
        }

        return 'enabled';
    }

    public function mailboxAccessLabel(): string
    {
        return match ($this->mailboxAccessState()) {
            'receive_disabled' => 'E-Mail-Empfang deaktiviert',
            'blocked' => 'gesperrt',
            default => 'aktiviert',
        };
    }

    public function mailboxAccessIcon(): string
    {
        return match ($this->mailboxAccessState()) {
            'receive_disabled' => 'eye-slash',
            'blocked' => 'ban',
            default => 'check',
        };
    }

    public function mailboxAccessRowClass(): string
    {
        return match ($this->mailboxAccessState()) {
            'receive_disabled' => 'mailbox-row-state-receive-disabled',
            'blocked' => 'mailbox-row-state-blocked',
            default => 'mailbox-row-state-enabled',
        };
    }

    public function nextMailboxAccessState(): string
    {
        return match ($this->mailboxAccessState()) {
            'enabled' => 'receive_disabled',
            'receive_disabled' => 'blocked',
            default => 'enabled',
        };
    }
}
