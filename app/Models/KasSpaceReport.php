<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasSpaceReport extends Model
{
    protected $table = 'kas_space_reports';

    protected $fillable = [
        'kas_login',
        'client_id',
        'measured_at',
        'used_kb',
        'max_kb',
        'data_json',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
        'data_json' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(KasClient::class, 'client_id');
    }
}

