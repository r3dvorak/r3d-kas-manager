<?php
/**
 * R3D KAS Manager – User Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.12.1-alpha
 * @date      2025-10-05
 *
 * @copyright (C) 2025
 * @license   MIT License
 *
 * Admin / API User model (guard: web)
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use Notifiable, HasFactory;

    /**
     * Attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'login',
        'email',
        'password',
        'role',
        'kas_client_id',
    ];

    /**
     * Attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts for model attributes.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Determine if this user has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
