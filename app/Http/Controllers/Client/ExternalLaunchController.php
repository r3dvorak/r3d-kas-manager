<?php
/**
 * R3D KAS Manager - External Launch Controller
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.13-alpha
 * @date      2026-02-17
 * @license   MIT License
 */

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasClient;
use App\Services\ExternalLaunchTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExternalLaunchController extends Controller
{
    public function create(Request $request, string $tool, ExternalLaunchTokenService $service)
    {
        /** @var KasClient $client */
        $client = Auth::guard('kas_client')->user();
        $payload = $service->createForClient($client, $tool, $request);

        return redirect()->route('external-launch.consume', ['token' => $payload['token']]);
    }

    public function consume(Request $request, string $token, ExternalLaunchTokenService $service)
    {
        $record = $service->consume($token, $request);
        if (!$record) {
            abort(403, 'Ungueltiger oder abgelaufener Launch-Token.');
        }

        return redirect()->away($record->target_url);
    }
}
