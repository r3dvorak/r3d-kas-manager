<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.13.3-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * app\Models\KasSubdomain.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasSubdomain extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kas_client_id',
        'domain_id',
        'subdomain_name',
        'subdomain_full',
        'subdomain_path',
        'php_version',
        'redirect_status',
        'redirect_target',
        'ssl_status',
        'active',
    ];

    public function kasClient()
    {
        return $this->belongsTo(KasClient::class);
    }

    public function domain()
    {
        return $this->belongsTo(KasDomain::class, 'domain_id');
    }
}
