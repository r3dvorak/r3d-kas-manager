<?php
/**
 * R3D KAS Manager – Model: KasDnsRecord
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.23.4-alpha
 * @date      2025-10-08
 * @license   MIT License
 *
 * Represents a single DNS record retrieved via KAS API (get_dns_settings).
 * Linked to kas_domains through domain_id.
 * Used for synchronized DNS zone data snapshots.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasDnsRecord extends Model
{
    protected $table = 'kas_dns_records';

    protected $fillable = [
        'kas_login',
        'domain_id',
        'record_zone',
        'record_name',
        'record_type',
        'record_data',
        'record_aux',
        'record_id_kas',
        'record_changeable',
        'record_deleteable',
        'data_json',
    ];

    /**
     * Relation: belongs to a domain
     */
    public function domain()
    {
        return $this->belongsTo(KasDomain::class, 'domain_id');
    }

    /**
     * Scope: limit to DNS records whose domains are active (not soft deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereHas('domain', fn($q) => $q->whereNull('deleted_at'));
    }
}
