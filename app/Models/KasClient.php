<?php
/**
 * R3D KAS Manager
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.6.6-alpha
 * @date      2025-09-26
 *
 * @copyright (C) 2025
 * @license   MIT License
 */

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class KasClient extends Authenticatable
{
    protected $fillable = [
        'name',
        'login',
        'domain',
        'api_user',
        'api_password',
    ];

    protected $hidden = [
        'api_password',
    ];
}
