<?php
/**
 * R3D KAS Manager - External Launch Token Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.13-alpha
 * @date      2026-02-17
 * @license   MIT License
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalLaunchToken extends Model
{
    protected $fillable = [
        'token_hash',
        'kas_client_id',
        'tool',
        'target_url',
        'workspace_key',
        'expires_at',
        'used_at',
        'used_ip',
        'used_user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function kasClient()
    {
        return $this->belongsTo(KasClient::class, 'kas_client_id');
    }
}
