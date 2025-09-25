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
 * Extends Laravel User model with role and kas_client_id.
 */

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
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
        return $this->role === 'admin';
    }
}
