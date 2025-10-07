<?php
/**
 * R3D KAS Manager – KasClient Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.15.1-alpha
 * @date      2025-10-06
 * @license   MIT License
 *
 * Hosting Client model (guard: kas_client)
 */

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class KasClient extends Authenticatable
{
    use HasFactory;

    /**
     * Attributes that are mass assignable.
     */
    protected $fillable = [
        'account_login',
        'account_password',   // for API (encrypted or plain)
        'password',           // hashed for Laravel login
        'account_comment',
        'account_contact_mail',
        'max_account',
        'max_domain',
        'max_subdomain',
        'max_webspace',
        'max_mail_account',
        'max_mail_forward',
        'max_mail_list',
        'max_databases',
        'max_ftpuser',
        'max_sambauser',
        'max_cronjobs',
        'max_wbk',
        'inst_htaccess',
        'inst_fpse',
        'inst_software',
        'kas_access_forbidden',
        'logging',
        'statistic',
        'logage',
        'show_password',
        'dns_settings',
        'show_direct_links',
        'ssh_access',
        'used_account_space',
        'account_2fa',
        'show_direct_links_wbk',
        'show_direct_links_sambausers',
        'show_direct_links_accounts',
        'show_direct_links_mailaccounts',
        'show_direct_links_ftpuser',
        'show_direct_links_databases',
        'in_progress',
    ];

    /**
     * Hidden attributes when serialized.
     */
    protected $hidden = [
        'account_password',
        'password',
        'remember_token',
    ];

    /**
     * Authentication uses the KAS login.
     */
    public function getAuthIdentifierName()
    {
        return 'account_login';
    }

    /**
     * Set hashed login password automatically.
     */
    public function setPasswordAttribute($value)
    {
        if ($value && !str_starts_with($value, '$2y$')) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    /**
     * Encrypt API password when saving (optional).
     */
    public function setAccountPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['account_password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt API password when accessed (optional).
     */
    public function getAccountPasswordAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value; // fallback if not encrypted
        }
    }

    // ------------------------------------------------------------
    // 🔗 Relationships
    // ------------------------------------------------------------

    /**
     * One client has many domains.
     */
    public function domains()
    {
        return $this->hasMany(KasDomain::class);
    }

    /**
     * One client has many subdomains (through domains).
     */
    public function subdomains()
    {
        return $this->hasManyThrough(KasSubdomain::class, KasDomain::class);
    }

    /**
     * One client has many mail accounts.
     */
    public function mailaccounts()
    {
        return $this->hasMany(KasMailAccount::class);
    }

    /**
     * One client has many mail forwards.
     */
    public function mailforwards()
    {
        return $this->hasMany(KasMailForward::class);
    }

    /**
     * One client has many databases.
     */
    public function databases()
    {
        return $this->hasMany(KasDatabase::class);
    }

    /**
     * One client has many cronjobs.
     */
    public function cronjobs()
    {
        return $this->hasMany(KasCronjob::class);
    }

    // ------------------------------------------------------------
    // 🧭 Convenience helpers
    // ------------------------------------------------------------

    /**
     * Returns all domain names as comma-separated string.
     */
    public function domainList(): string
    {
        return $this->domains->pluck('domain_full')->join(', ');
    }

    /**
     * Returns the client’s main (first active) domain.
     */
    public function mainDomain()
    {
        return $this->domains()->where('active', 1)->first();
    }

    /**
     * Returns used storage in GB (formatted).
     */
    public function usedSpaceGb(): float
    {
        return round(($this->used_account_space ?? 0) / 1024, 2);
    }
}
