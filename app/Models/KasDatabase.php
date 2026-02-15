<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasDatabase extends Model
{
    protected $table = 'kas_databases';

    protected $fillable = [
        'kas_login',
        'client_id',
        'database_login',
        'database_comment',
        'database_allowed_hosts',
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

