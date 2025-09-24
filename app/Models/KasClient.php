<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.1.0-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license     GNU General Public License version 2 or later
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_user',
        'api_password',
    ];
}
