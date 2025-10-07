<?php
/**
 * R3D KAS Manager – KasDomain Model
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.16.0-alpha
 * @date      2025-10-07
 * 
 * @license   MIT License
 * @copyright (C) 2025 Richard Dvořák
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasDomain extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kas_client_id',
        'domain_name',
        'domain_tld',
        'domain_full',
        'domain_path',
        'domain_redirect_status',
        'dummy_host',
        'fpse_active',
        'dkim_selector',
        'statistic_language',
        'statistic_version',
        'ssl_proxy',
        'ssl_certificate_ip',
        'ssl_certificate_sni',
        'php_version',
        'php_deprecated',
        'is_active',
        'in_progress',
    ];

    /**
     * One domain belongs to a single KAS client.
     */
    public function kasClient()
    {
        return $this->belongsTo(KasClient::class, 'kas_client_id');
    }

    /**
     * One domain can have many subdomains.
     */
    public function subdomains()
    {
        return $this->hasMany(KasSubdomain::class, 'domain_id');
    }

    /**
     * Convenience: full domain label.
     */
    public function label(): string
    {
        return $this->domain_full ?? "{$this->domain_name}.{$this->domain_tld}";
    }
}
