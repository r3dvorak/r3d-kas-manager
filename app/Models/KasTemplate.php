<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.2-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * app\Models\KasTemplate.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasTemplate extends Model
{
    protected $table = 'kas_templates';

    protected $guarded = [];

    protected $casts = [
        'data_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
