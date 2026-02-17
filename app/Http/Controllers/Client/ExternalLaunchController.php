<?php
/**
 * R3D KAS Manager - External Launch Controller
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvorak
 * @version   0.28.19-alpha
 * @date      2026-02-17
 * @license   MIT License
 */

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasClient;
use App\Models\KasDatabase;
use App\Models\KasMailAccount;
use App\Services\ExternalLaunchTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExternalLaunchController extends Controller
{
    public function create(Request $request, string $tool, ExternalLaunchTokenService $service)
    {
        /** @var KasClient $client */
        $client = Auth::guard('kas_client')->user();
        $context = [];

        if ($request->filled('mailbox')) {
            $mailbox = KasMailAccount::query()
                ->whereKey((int) $request->query('mailbox'))
                ->where('client_id', (int) $client->id)
                ->firstOrFail();
            $context['mailbox_id'] = (int) $mailbox->id;
            $context['mailbox_email'] = (string) ($mailbox->email ?? '');
        }

        if ($request->filled('database')) {
            $database = KasDatabase::query()
                ->whereKey((int) $request->query('database'))
                ->where('client_id', (int) $client->id)
                ->firstOrFail();
            $context['database_id'] = (int) $database->id;
            $context['database_login'] = (string) ($database->database_login ?? '');
        }

        $payload = $service->createForClient($client, $tool, $request, $context);

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
