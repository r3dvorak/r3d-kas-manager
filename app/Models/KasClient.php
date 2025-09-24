<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.5.0-alpha
 * @date      2025-09-25
 * 
 * @copyright (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Represents a KAS client account with credentials.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasClient extends Model
{
    protected $fillable = [
        'name',
        'kas_login',
        'kas_auth_data',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
