<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.7-alpha
 * @date      2025-09-24
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * User Model with login (API user) + kas_client_id
 */

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'login',         // API-User wie w01e77bc
        'password',
        'role',
        'kas_client_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function kasClient()
    {
        return $this->belongsTo(KasClient::class);
    }

    public function isAdmin(): bool
    {
        return ($this->role === 'admin');
    }
}
