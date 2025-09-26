<?php
/**
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.6-alpha
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
use Carbon\Carbon;

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
        'used' => 'bool',
    ];

    public static function generateForClient(int $kasClientId, ?int $createdBy = null, int $minutes = 5): self
    {
        $token = Str::random(48);
        $expires = Carbon::now()->addMinutes($minutes);

        return static::create([
            'token' => hash('sha256', $token),
            // we store hashed token server-side - actual token returned to client is raw
            // but to avoid storing raw tokens. Implementation will return raw separately.
            'kas_client_id' => $kasClientId,
            'created_by' => $createdBy,
            'expires_at' => $expires,
            'used' => false,
        ])->setRawToken($token);
    }

    // Temporary in-memory raw token (not persisted). Helper for controller.
    protected $rawToken;

    public function setRawToken(string $raw)
    {
        $this->rawToken = $raw;
        return $this;
    }

    public function getRawToken(): string
    {
        return $this->rawToken;
    }

    public static function findByRawToken(string $raw)
    {
        return static::where('token', hash('sha256', $raw))->first();
    }

    public function kasClient()
    {
        return $this->belongsTo(KasClient::class);
    }
}
