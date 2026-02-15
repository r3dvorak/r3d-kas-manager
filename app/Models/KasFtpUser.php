<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasFtpUser extends Model
{
    protected $table = 'kas_ftp_users';

    protected $fillable = [
        'kas_login',
        'client_id',
        'ftp_login',
        'ftp_path',
        'ftp_comment',
        'perm_read',
        'perm_write',
        'perm_list',
        'status',
        'data_json',
    ];

    protected $casts = [
        'data_json' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(KasClient::class, 'client_id');
    }
}

