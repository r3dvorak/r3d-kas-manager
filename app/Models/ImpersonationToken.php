<?php
/**
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.7-alpha
 * @date      2025-09-26
 *
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 *
 * Model for single-use impersonation tokens.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ImpersonationToken extends Model
{
    protected $fillable = [
        'token',
        'kas_client_id',
        'created_by',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used'       => 'boolean',
    ];

    /**
     * Generate a new token for a KAS client
     */
    public static function generateForClient(int $kasClientId, ?int $createdBy = null, int $minutes = 5): self
    {
        $rawToken = Str::random(48);
        $hashed   = hash('sha256', $rawToken);

        $instance = static::create([
            'token'        => $hashed,
            'kas_client_id'=> $kasClientId,
            'created_by'   => $createdBy,
            'expires_at'   => Carbon::now()->addMinutes($minutes),
            'used'         => false,
        ]);

        return $instance->setRawToken($rawToken);
    }

    /**
     * Keep raw token in memory (never persisted).
     */
    protected ?string $rawToken = null;

    public function setRawToken(string $raw): self
    {
        $this->rawToken = $raw;
        return $this;
    }

    public function getRawToken(): ?string
    {
        return $this->rawToken;
    }

    /**
     * Find token from raw string (after hashing).
     */
    public static function findByRawToken(string $raw): ?self
    {
        return static::where('token', hash('sha256', $raw))->first();
    }

    /**
     * Check if token is still valid (not used + not expired).
     */
    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Mark token as used.
     */
    public function markUsed(): void
    {
        $this->used = true;
        $this->save();
    }

    /**
     * Relation to KAS Client
     */
    public function kasClient()
    {
        return $this->belongsTo(KasClient::class);
    }
}
