<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionLocale = $request->session()->get('locale');
        $clientLocale = Auth::guard('kas_client')->check()
            ? (string) (Auth::guard('kas_client')->user()?->preferred_locale ?? '')
            : '';
        $adminLocale = Auth::guard('web')->check()
            ? (string) (Auth::guard('web')->user()?->preferred_locale ?? '')
            : '';

        $locale = in_array($sessionLocale, ['de', 'en'], true)
            ? $sessionLocale
            : (in_array($clientLocale, ['de', 'en'], true)
                ? $clientLocale
                : (in_array($adminLocale, ['de', 'en'], true) ? $adminLocale : config('app.locale', 'de')));

        app()->setLocale($locale);

        return $next($request);
    }
}

