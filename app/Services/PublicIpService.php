<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PublicIpService
{
    public function getPublicIp(): string
    {
        return Cache::remember('hints.public_ip', 600, function () {
            try {
                $res = Http::timeout(2)->get('https://api.ipify.org', [
                    'format' => 'json',
                ]);

                if ($res->ok()) {
                    $ip = (string) ($res->json('ip') ?? '');
                    if ($ip !== '') {
                        return $ip;
                    }
                }
            } catch (\Throwable $e) {
                // Fall back below.
            }

            return (string) request()->ip();
        });
    }
}

