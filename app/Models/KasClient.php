<?php
/**
 * R3D KAS Manager – KasClient Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.7.2-alpha
 * @date      2025-09-27
 * @license   MIT License
 * 
 * app\Models\KasClient.php
 */

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class KasClient extends Authenticatable
{
    protected $fillable = [
        'name',
        'login',
        'email',
        'domain',
        'api_user',
        'api_password',
        'role',
    ];

    protected $hidden = [
        'api_password',
        'password',
        'remember_token',
    ];

    /**
     * Laravel soll "login" als Identifier verwenden (statt email).
     */
    public function getAuthIdentifierName()
    {
        return 'login';
    }
}
