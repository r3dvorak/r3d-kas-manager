<?php
/**
 * R3D KAS Manager - External Launch Audit Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.13-alpha
 * @date      2026-02-17
 * @license   MIT License
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalLaunchAudit extends Model
{
    protected $fillable = [
        'external_launch_token_id',
        'kas_client_id',
        'event',
        'tool',
        'workspace_key',
        'ip',
        'user_agent',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];
}
